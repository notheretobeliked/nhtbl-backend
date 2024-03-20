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