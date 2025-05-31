<?php
/**
 * Plugin Name: Suppress Translation Loading Notices
 * Description: Suppresses "doing it wrong" notices for plugins that load translations too early
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Suppress translation loading notices for plugins that load too early
 */
add_filter('doing_it_wrong_trigger_error', function ($trigger, $function_name, $message, $version) {
    // Check if this is a translation loading notice
    if (strpos($message, 'Translation loading for the') !== false) {
        // Check if it's for one of our known problematic plugins
        if (strpos($message, 'acf') !== false || 
            strpos($message, 'wp-graphql') !== false || 
            strpos($message, 'wpgraphql-acf') !== false) {
            return false; // Don't trigger the error
        }
    }
    
    return $trigger; // Allow other errors to show
}, 10, 4);

/**
 * Alternative approach: Hook into the specific function that's causing issues
 */
add_filter('doing_it_wrong_run', function ($trigger, $function, $message, $version = null) {
    if ($function === '_load_textdomain_just_in_time' && 
        (strpos($message, 'wp-graphql') !== false || 
         strpos($message, 'wpgraphql-acf') !== false || 
         strpos($message, 'acf') !== false)) {
        return false;
    }
    return $trigger;
}, 10, 3); 