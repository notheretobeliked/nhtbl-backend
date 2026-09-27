<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Rebuild the frontend when published content changes.
 *
 * Posts VERCEL_WEBHOOK (a Vercel Deploy Hook URL) on staging/production. Changes
 * are debounced: the first change schedules a rebuild one minute out and later
 * changes in that window ride along, so a burst of edits costs one deploy.
 *
 * Hooked on wp_after_insert_post, which fires for block-editor (REST) saves too,
 * after meta is written. Covers every public post type; projects narrow or extend
 * it with the `sage/frontend_rebuild_post_types` filter.
 */
const FRONTEND_REBUILD_HOOK = 'sage_frontend_rebuild';
const FRONTEND_REBUILD_DELAY = MINUTE_IN_SECONDS;

add_action('wp_after_insert_post', function ($post_id, $post, $update, $post_before) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $types = get_post_types(['public' => true]);
    unset($types['attachment']);
    $types = apply_filters('sage/frontend_rebuild_post_types', array_values($types));
    if (!in_array($post->post_type, $types, true)) {
        return;
    }

    // Only when the live site is affected: published, unpublished or trashed.
    $was_published = $post_before && $post_before->post_status === 'publish';
    if ($post->post_status !== 'publish' && !$was_published) {
        return;
    }

    schedule_frontend_rebuild();
}, 10, 4);

/**
 * Queue a frontend rebuild (no-op outside staging/production or without a hook URL).
 */
function schedule_frontend_rebuild(): void
{
    if (!in_array(env('WP_ENV'), ['staging', 'production'], true) || !env('VERCEL_WEBHOOK')) {
        return;
    }

    if (!wp_next_scheduled(FRONTEND_REBUILD_HOOK)) {
        wp_schedule_single_event(time() + FRONTEND_REBUILD_DELAY, FRONTEND_REBUILD_HOOK);
    }
}

add_action(FRONTEND_REBUILD_HOOK, function () {
    $url = env('VERCEL_WEBHOOK');
    if (!$url) {
        return;
    }

    $response = wp_remote_post($url, ['timeout' => 10]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 300) {
        error_log('Frontend rebuild webhook failed: ' . (is_wp_error($response)
            ? $response->get_error_message()
            : wp_remote_retrieve_response_code($response)));
    }
});


/**
 * Enable Application Passwords in development (without HTTPS requirement)
 */
add_filter('wp_is_application_passwords_available', function ($available) {
    // Force enable in development environment
    if (env('WP_ENV') === 'development') {
        return true;
    }
    return $available;
});

/**
 * Suppress EXIF read errors during REST API media uploads.
 * Some images have corrupted/non-standard EXIF data that causes exif_read_data() to emit warnings.
 * Acorn's HandleExceptions converts these warnings to ErrorExceptions, causing 500 errors.
 * This sets up a custom error handler to suppress exif_read_data warnings during media uploads.
 */
add_action('rest_api_init', function () {
    // Only apply to media endpoint
    add_filter('rest_pre_dispatch', function ($result, $server, $request) {
        $route = $request->get_route();

        // Only intercept media uploads
        if ($route === '/wp/v2/media' && $request->get_method() === 'POST') {
            // Set custom error handler that suppresses EXIF warnings
            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                // Suppress exif_read_data warnings
                if (strpos($errstr, 'exif_read_data') !== false) {
                    return true; // Suppress the error
                }
                // Let other errors through to normal handler
                return false;
            }, E_WARNING | E_NOTICE);

            // Restore error handler after request completes
            add_filter('rest_post_dispatch', function ($response) {
                restore_error_handler();
                return $response;
            }, 10, 1);
        }

        return $result;
    }, 10, 3);
});

/**
 * Suppress the spurious Interactivity API warning triggered while resolving block
 * attributes over GraphQL.
 *
 * wp-graphql-content-blocks (4.8.6) calls render_block() for every block attribute
 * field it resolves, even for attributes that have no `source` and are read straight
 * from the parsed block attrs. Rendering an interactive inner block in isolation —
 * e.g. WP 7.0's core/accordion-item, which stamps data-wp-class--is-open — leaves the
 * directive without the namespace its parent core/accordion would normally supply, so
 * WP_Interactivity_API::evaluate() fires _doing_it_wrong(). With WP_DEBUG on, Acorn
 * escalates that to an exception, WPGraphQL reports "Internal server error" for the
 * field, and because attributes like openByDefault are non-null the error propagates
 * up and nulls the entire `attributes` object.
 *
 * Only the first affected block on a page shows the fault, since _doing_it_wrong()
 * de-duplicates identical messages within a request.
 *
 * Remove this once wp-graphql-content-blocks stops rendering blocks to resolve
 * attributes that carry no `source`.
 *
 * @see https://github.com/wpengine/wp-graphql-content-blocks/blob/main/includes/Blocks/Block.php
 */
add_filter('doing_it_wrong_trigger_error', function ($trigger, $function_name) {
    if ($function_name === 'WP_Interactivity_API::evaluate' && is_graphql_request()) {
        return false;
    }

    return $trigger;
}, 10, 2);

/**
 * Expose the per-page "hide from search engines" choice as ContentNode.noindex.
 *
 * Editors set it in the Yoast panel (Advanced > "Allow search engines to show
 * this content in search results?" = No), stored as _yoast_wpseo_meta-robots-noindex = 1.
 * The frontend uses it to keep a page out of the sitemap and emit a robots
 * noindex tag while still prerendering it (a "hidden" page).
 *
 * Deliberately NOT Yoast's seo.metaRobotsNoindex: that reports the effective value,
 * which turns into "noindex" for every page when the backend itself discourages
 * indexing (blog_public = 0, e.g. Bedrock's DISALLOW_INDEXING outside production, or
 * a delisted headless backend) — which would hide the whole frontend.
 */
add_action('graphql_register_types', function () {
    register_graphql_field('ContentNode', 'noindex', [
        // Nullable on purpose: ContentNode includes MediaItem, and a non-null field
        // would make every hand-built MediaItem/ContentNode type in a frontend
        // require it. The resolver always returns true/false.
        'type' => 'Boolean',
        'description' => __('True when this item is set to be hidden from search engines (Yoast > Advanced), ignoring site-wide indexing settings.', 'sage'),
        'resolve' => function ($node) {
            return get_post_meta($node->databaseId, '_yoast_wpseo_meta-robots-noindex', true) === '1';
        },
    ]);
});
