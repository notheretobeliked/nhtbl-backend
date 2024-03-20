<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class PortfolioBlock extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Portfolio Block';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A simple Portfolio Block block.';

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
     * The block styles.
     *
     * @var array
     */
    public $styles = [
        [
            'name' => 'light',
            'label' => 'Light',
            'isDefault' => true,
        ],
        [
            'name' => 'dark',
            'label' => 'Dark',
        ],
    ];

    /**
     * The block preview example data.
     *
     * @var array
     */
    public $example = [
        'items' => [['item' => 'Item one'], ['item' => 'Item two'], ['item' => 'Item three']],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/heading' => ['placeholder' => 'Hello World'],
        'core/paragraph' => ['placeholder' => 'Welcome to the Portfolio Block block.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'portfolioItems' => $this->portfolioItems(),
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $portfolioBlock = Builder::make('portfolio_block');

        $portfolioBlock->addRelationship('portfolio_items', [
            'label' => 'Projects',
            'instructions' => 'Choose projects',
            'required' => 1,
            'conditional_logic' => [],
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'post_type' => ['project'],
            'taxonomy' => [],
            'elements' => '',
            'min' => '1',
            'max' => '50',
            'return_format' => 'object',
        ]);

        return $portfolioBlock->build();
    }

    /**
     * Return the items field.
     *
     * @return array
     */
    public function portfolioItems()
    {
        $items = get_field('portfolio_items');
        if ($items) {
            $itemsArray = [];
            foreach ($items as $item) {
                $itemsArray[] = [
                    'title' => get_the_title($item),
                    'image' => get_the_post_thumbnail($item),
                    'url' => get_the_permalink($item),
                ];
            }
        }
        return $itemsArray ?: null;
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
