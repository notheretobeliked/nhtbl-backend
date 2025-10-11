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
 * Add first image from image gallery as featured image
 * 
 * @return void
 */

add_filter('acf/save_post', function ($post_id) {
	$gallery = get_field('image_gallery', $post_id, false);
	if (!empty($gallery) && !has_post_thumbnail($post_id)) {
		$image_id = $gallery[0];
		set_post_thumbnail($post_id, $image_id);
	}
});

/**
 * Auto-generate question keys when question text is saved
 */
add_filter('acf/update_value/name=question_text', function ($value, $post_id, $field, $original) {
    // Get the parent field (questions repeater)
    $parent = $field['parent'] ?? null;
	error_log($parent);
    
    if ($parent) {
        // Get current row index
        $row_index = acf_maybe_get($field, 'row_index');
		error_log(print_r($row_index, true));
        
        if ($row_index !== null) {
            // Get the question_key field for this row
            $question_key = get_sub_field('question_key');
            
            // Generate key if empty
            if (empty($question_key) && !empty($value)) {
                update_sub_field('question_key', generate_unique_key($value));
            }
        }
    }
    
    return $value;
}, 10, 4);

/**
 * Auto-generate option values when option label is saved
 */
add_filter('acf/update_value/name=option_label', function ($value, $post_id, $field, $original) {
    // Get the parent field (options repeater)
    $parent = $field['parent'] ?? null;
    
    if ($parent) {
        // Get current row index
        $row_index = acf_maybe_get($field, 'row_index');
        
        if ($row_index !== null) {
            // Get the option_value field for this row
            $option_value = get_sub_field('option_value');
            
            // Generate value if empty
            if (empty($option_value) && !empty($value)) {
                update_sub_field('option_value', generate_unique_key($value, '', 30));
            }
        }
    }
    
    return $value;
}, 10, 4);

/**
 * Generate unique key from text
 */
function generate_unique_key($text, $prefix = '', $max_length = 40) {
    // Convert to lowercase
    $key = strtolower($text);
    
    // Remove special characters, keep alphanumeric and spaces
    $key = preg_replace('/[^a-z0-9\s]/', '', $key);
    
    // Replace spaces with hyphens
    $key = preg_replace('/\s+/', '-', trim($key));
    
    // Limit length
    $key = substr($key, 0, $max_length);
    
    // Add prefix if provided
    if ($prefix) {
        $key = $prefix . '-' . $key;
    }
    
    // Remove trailing hyphens
    $key = rtrim($key, '-');
    
    // Add 5 random characters for uniqueness
    $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 5);
    $key = $key . '-' . $random;
    
    return $key;
}