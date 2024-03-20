<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Projects extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.content-project',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'postContent' => $this->postContent(),
        ];
    }


    /**
     * Retrieve the posts.
     *
     * @return string
     */
    public function postContent() 
    {
        global $post;
            return [
                'title' => get_the_title($post),
                'image' => get_the_post_thumbnail($post),
                'url'   => get_the_permalink($post),
            ];

    }

     /**
     * Retrieve the post images.
     *
     * @return array
     */
    public function images()
    {
        return get_field('image_gallery', get_the_id());
    }

}
