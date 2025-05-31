<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class HomePageHero extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Home page Hero';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Homepage hero block.';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'formatting';

    /**
     * The block icon.
     *
     * @var string|array
     */
    public $icon = 'editor-ul';

    /**
     * The block keywords.
     *
     * @var array
     */
    public $keywords = [];

    /**
     * The block post type allow list.
     *
     * @var array
     */
    public $post_types = [];

    /**
     * The parent block type allow list.
     *
     * @var array
     */
    public $parent = [];

    /**
     * The ancestor block type allow list.
     *
     * @var array
     */
    public $ancestor = [];

    /**
     * The default block mode.
     *
     * @var string
     */
    public $mode = 'preview';

    /**
     * The default block alignment.
     *
     * @var string
     */
    public $align = '';

    /**
     * The default block text alignment.
     *
     * @var string
     */
    public $align_text = '';

    /**
     * The default block content alignment.
     *
     * @var string
     */
    public $align_content = '';

    /**
     * The supported block features.
     *
     * @var array
     */
    public $supports = [
        'align' => true,
        'align_text' => false,
        'align_content' => false,
        'full_height' => false,
        'anchor' => false,
        'mode' => false,
        'multiple' => true,
        'jsx' => true,
        'color' => [
            'background' => true,
            'text' => true,
            'gradient' => true,
        ],
    ];



    /**
     * The block preview example data.
     *
     * @var array
     */
    public $example = [
        'items' => [
            ['item' => 'Item one'],
            ['item' => 'Item two'],
            ['item' => 'Item three'],
        ],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/paragraph' => ['placeholder' => 'Long text goes here.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'images' => $this->images(),
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $homePageHero = Builder::make('home_page_hero');

        $homePageHero
            ->addGallery('images');

        return $homePageHero->build();
    }

    /**
     * Return the items field.
     *
     * @return array
     */
    public function images()
    {
        return get_field('images');
    }

    /**
     * Assets enqueued when rendering the block.
     *
     * @param  array $block
     * @return void
     */
    public function assets($block)
    {
        //
    }
}
