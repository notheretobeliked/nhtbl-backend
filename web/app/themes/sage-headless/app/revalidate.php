<?php

/**
 * Frontend cache invalidation for the headless SvelteKit site.
 *
 * Content edits trigger targeted ISR revalidation of just the affected paths
 * (fast, cheap). Site-wide structural changes (menus, ACF options) trigger a
 * full Vercel rebuild instead, since they affect every cached page.
 *
 * NB: this fires on REST requests too — Gutenberg saves via REST, so the old
 * `!REST_REQUEST` guard meant publishing in the block editor never revalidated.
 */

namespace App;

/**
 * Whether cache invalidation should run in the current environment.
 */
function revalidation_enabled(): bool
{
    return in_array(env('WP_ENV'), ['staging', 'production'], true);
}

/**
 * POST a list of paths to the frontend's on-demand ISR revalidation endpoint.
 *
 * @param string[] $paths
 */
function revalidate_paths(array $paths): void
{
    $frontend = env('FRONTEND_HOST');
    $token    = env('VERCEL_ISR_TOKEN');

    if (!$frontend || !$token) {
        return;
    }

    $paths = array_values(array_unique(array_filter($paths)));
    if (empty($paths)) {
        return;
    }

    wp_remote_post(rtrim($frontend, '/') . '/api/revalidate', [
        'timeout'  => 5,
        'blocking' => false, // don't block the editor save
        'headers'  => ['Content-Type' => 'application/json'],
        'body'     => wp_json_encode(['token' => $token, 'paths' => $paths]),
    ]);
}

/**
 * Trigger a full Vercel rebuild via deploy hook (for site-wide changes).
 */
function trigger_full_deploy(): void
{
    $hook = env('VERCEL_WEBHOOK');
    if (!$hook) {
        return;
    }

    wp_remote_post($hook, ['timeout' => 5, 'blocking' => false]);
}

/**
 * Published pages that embed an acf/portfolio-block. Projects only surface on
 * the front end through these blocks, so they must refresh when a project
 * changes. The `s` arg narrows the DB query; has_block() confirms the match.
 *
 * @return string[]
 */
function pages_with_portfolio_block(): array
{
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        's'              => 'acf/portfolio-block',
    ]);

    $paths = [];
    foreach ($pages as $id) {
        if (has_block('acf/portfolio-block', $id)) {
            $path = wp_parse_url(get_permalink($id), PHP_URL_PATH);
            if ($path) {
                $paths[] = $path;
            }
        }
    }

    return $paths;
}

/**
 * Paths to revalidate for a given post.
 *
 * @return string[]
 */
function revalidation_paths_for(\WP_Post $post): array
{
    $paths = ['/']; // the homepage can surface recent/featured content

    $own = wp_parse_url(get_permalink($post), PHP_URL_PATH);
    if ($own) {
        $paths[] = $own;
    }

    if ($post->post_type === 'project') {
        $paths = array_merge($paths, pages_with_portfolio_block());
    }

    return $paths;
}

/**
 * Revalidate affected paths whenever a post enters or leaves the published
 * state (publish / update / unpublish / trash).
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if (!revalidation_enabled() || !($post instanceof \WP_Post)) {
        return;
    }

    if (wp_is_post_revision($post) || wp_is_post_autosave($post)) {
        return;
    }

    if (!in_array($post->post_type, ['post', 'page', 'project'], true)) {
        return;
    }

    // Only when the published state is involved on one side of the transition.
    if ($new_status !== 'publish' && $old_status !== 'publish') {
        return;
    }

    revalidate_paths(revalidation_paths_for($post));
}, 10, 3);

/**
 * Nav menus render on every page (header/footer), so a menu change requires a
 * full rebuild rather than per-path revalidation.
 */
add_action('wp_update_nav_menu', function () {
    if (!revalidation_enabled()) {
        return;
    }

    trigger_full_deploy();
});

/**
 * ACF options pages hold site-wide settings → full rebuild.
 */
add_action('acf/save_post', function ($post_id) {
    if ($post_id !== 'options' || !revalidation_enabled()) {
        return;
    }

    trigger_full_deploy();
}, 20);
