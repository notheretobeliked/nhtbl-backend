<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class SingleProject extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.content-single-project',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'title' => $this->title(),
            'content' => $this->content(),
            'images' => $this->images(),
        ];
    }

    /**
     * Retrieve the post title.
     *
     * @return string
     */
    public function title()
    {
        return get_the_title();
    }

    /**
     * Retrieve the post content.
     *
     * @return string
     */
    public function content()
    {
        return get_the_content();
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
