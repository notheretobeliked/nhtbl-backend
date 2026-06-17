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

// Frontend cache invalidation (ISR revalidation + full-deploy hooks) lives in
// app/revalidate.php.


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
 * Load nhtbl project-specific backend code (custom blocks' GraphQL, portfolio
 * authoring, survey, subpage nav, image colours, animated images). Kept in its
 * own file so the template's setup.php / filters.php stay merge-clean.
 */
require_once __DIR__ . '/nhtbl.php';
