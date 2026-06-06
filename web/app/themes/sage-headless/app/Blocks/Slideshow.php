<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class Slideshow extends Block
{
    public $name = 'Slideshow';

    public $description = 'A slideshow of image slides with optional captions and side navigation.';

    public $category = 'formatting';

    public $icon = 'images-alt2';

    public $keywords = ['slideshow', 'carousel', 'slides', 'gallery'];

    /**
     * Allowed on the portfolio (project) CPT plus standard content types.
     */
    public $post_types = ['project', 'page', 'post'];

    public $parent = [];

    public $ancestor = [];

    public $mode = 'preview';

    public $blockVersion = 3;

    public $autoInlineEditing = false;

    public $align = '';

    public $supports = [
        'align' => true,
        'align_text' => false,
        'align_content' => false,
        'full_height' => false,
        'anchor' => true,
        'mode' => true,
        'multiple' => true,
        'jsx' => true, // enable InnerBlocks (the slides)
        'color' => [
            'background' => true,
            'text' => false,
            'gradients' => false,
        ],
        'spacing' => [
            'padding' => true,
            'margin' => true,
        ],
    ];

    /**
     * Inner block template — seed one empty slide so a new slideshow is usable
     * straight away (assoc format can't repeat a key; authors add more slides).
     * allowed_blocks (restricting to acf/slide) is set on the <InnerBlocks> tag
     * in the blade.
     */
    public $template = [
        'acf/slide' => [],
    ];

    /**
     * Editor-preview data. The frontend Svelte component renders the real
     * slideshow via WPGraphQL; this is preview-only.
     */
    public function with(): array
    {
        return [
            'autoplay' => (bool) (get_field('autoplay') ?? true),
            'interval_ms' => (int) (get_field('interval_ms') ?: 5000),
            'aspect_ratio' => get_field('aspect_ratio') ?: 'auto',
            'show_navigation' => (bool) (get_field('show_navigation') ?? true),
        ];
    }

    public function fields(): array
    {
        $fields = Builder::make('slideshow');
        $fields
            ->addTrueFalse('autoplay', [
                'label' => 'Autoplay',
                'instructions' => 'Automatically advance through the slides on a timer.',
                'default_value' => 1,
                'ui' => 1,
            ])
            ->addNumber('interval_ms', [
                'label' => 'Interval (ms)',
                'instructions' => 'Time each slide is shown before advancing.',
                'default_value' => 5000,
                'min' => 1000,
                'max' => 20000,
                'step' => 500,
                'conditional_logic' => [
                    [
                        ['field' => 'autoplay', 'operator' => '==', 'value' => '1'],
                    ],
                ],
            ])
            ->addSelect('aspect_ratio', [
                'label' => 'Aspect ratio / height',
                'choices' => [
                    'auto' => 'Auto (use first image)',
                    '1:1' => '1:1 (Square)',
                    '16:9' => '16:9 (Wide)',
                    '4:3' => '4:3',
                    '3:2' => '3:2',
                    '80vh' => '80vh (fixed — leaves room above the fold)',
                    '100vh' => '100vh (full viewport height)',
                ],
                'default_value' => 'auto',
                'return_format' => 'value',
            ])
            ->addTrueFalse('show_navigation', [
                'label' => 'Show navigation',
                'instructions' => 'Circular slide buttons down the right-hand side.',
                'default_value' => 1,
                'ui' => 1,
            ]);

        return $fields->build();
    }

    public function assets(array $block): void
    {
        //
    }
}
