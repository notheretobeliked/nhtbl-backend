<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class LinkBlock extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Link Block';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A block that outputs a link';

    /**
     * The block category.
     *
     * @var string
     */
    public $category = 'design';

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
    public $post_types = ['post', 'page', 'project'];

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
     * The default block spacing.
     *
     * @var array
     */
    public $spacing = [
        'padding' => null,
        'margin' => null,
    ];

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
        'mode' => true,
        'multiple' => true,
        'jsx' => true,
        'color' => [
            'background' => true,
            'text' => false,
            'gradients' => false,
        ],
        'spacing' => [
            'padding' => false,
            'margin' => false,
        ],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/group' => []
    ];

    /**
     * Data to be passed to the block before rendering.
     */
    public function with(): array
    {
        return [
            'link_type' => $this->linkType(),
            'internal_link' => $this->internalLink(),
            'external_link' => $this->externalLink(),
        ];
    }

    /**
     * The block field group.
     */
    public function fields(): array
    {
        $fields = Builder::make('link_block');

        $fields
            ->addSelect('link_type', [
                'label' => 'Link Type',
                'instructions' => 'Choose whether this is an internal or external link',
                'choices' => [
                    'internal' => 'Internal',
                    'external' => 'External',
                ],
                'default_value' => 'internal',
                'required' => 1,
            ])
            ->addRelationship('internal_link', [
                'label' => 'Internal Link',
                'instructions' => 'Select a page or post to link to',
                'post_type' => ['page', 'post', 'project'],
                'min' => 1,
                'max' => 1,
                'return_format' => 'object',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'link_type',
                            'operator' => '==',
                            'value' => 'internal',
                        ],
                    ],
                ],
            ])
            ->addLink('external_link', [
                'label' => 'External Link',
                'instructions' => 'Enter the external link details',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'link_type',
                            'operator' => '==',
                            'value' => 'external',
                        ],
                    ],
                ],
            ])
        ;

        return $fields->build();
    }

    /**
     * Retrieve the link type.
     *
     * @return string
     */
    public function linkType()
    {
        return get_field('link_type') ?: 'external';
    }

    /**
     * Retrieve the internal link.
     *
     * @return object|null
     */
    public function internalLink()
    {
        if ($this->linkType() === 'internal') {
            $link = get_field('internal_link');
            return is_array($link) && !empty($link) ? $link[0] : null;
        }
        return null;
    }

    /**
     * Retrieve the external link.
     *
     * @return array|null
     */
    public function externalLink()
    {
        if ($this->linkType() === 'external') {
            return get_field('external_link');
        }
        return null;
    }

    /**
     * Retrieve the items (for backwards compatibility).
     *
     * @return array
     */
    public function items()
    {
        // For backwards compatibility, return the appropriate link data
        if ($this->linkType() === 'internal') {
            return $this->internalLink();
        }
        return $this->externalLink();
    }

    /**
     * Assets enqueued with 'enqueue_block_assets' when rendering the block.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/#editor-content-scripts-and-styles
     */
    public function assets(array $block): void
    {
        //
    }
}
