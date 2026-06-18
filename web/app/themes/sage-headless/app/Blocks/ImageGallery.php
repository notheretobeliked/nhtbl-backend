<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class ImageGallery extends Block
{
    public $name = 'ImageGallery';

    public $description = 'A single image, or a crossfade gallery that cycles through a set of images on a timer.';

    public $category = 'formatting';

    public $icon = 'images-alt2';

    public $keywords = ['gallery', 'crossfade', 'cycle', 'slideshow'];

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

    public $align_text = '';

    public $align_content = '';

    public $spacing = [
        'padding' => null,
        'margin' => null,
    ];

    public $supports = [
        'align' => true,
        'align_text' => false,
        'align_content' => false,
        'full_height' => false,
        'anchor' => true,
        'mode' => true,
        'multiple' => true,
        'jsx' => false,
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

    public $template = [];

    /**
     * Data passed to the editor-preview blade. The frontend Svelte component
     * reads the ACF fields directly via WPGraphQL, so this is preview-only.
     */
    public function with(): array
    {
        return [
            'images' => get_field('images') ?: [],
            'interval_ms' => (int) (get_field('interval_ms') ?: 4000),
            'aspect_ratio' => get_field('aspect_ratio') ?: 'auto',
            'caption' => get_field('caption') ?: '',
            'full_width' => (bool) get_field('full_width'),
            'full_height' => (bool) get_field('full_height'),
        ];
    }

    public function fields(): array
    {
        $fields = Builder::make('image_gallery');
        $fields
            ->addGallery('images', [
                'label' => 'Images',
                'min' => 1,
                'max' => 30,
                'return_format' => 'array',
                'preview_size' => 'medium',
                'mime_types' => 'jpg, jpeg, png, webp, avif, svg',
            ])
            ->addNumber('interval_ms', [
                'label' => 'Interval (ms)',
                'instructions' => 'Time each image is shown before crossfading to the next.',
                'default_value' => 4000,
                'min' => 500,
                'max' => 20000,
                'step' => 500,
            ])
            ->addSelect('aspect_ratio', [
                'label' => 'Aspect ratio',
                'choices' => [
                    'auto' => 'Auto (use image dimensions)',
                    '1:1' => '1:1 (Square)',
                    '16:9' => '16:9 (Wide)',
                    '4:3' => '4:3',
                ],
                'default_value' => 'auto',
                'return_format' => 'value',
            ])
            ->addTextarea('caption', [
                'label' => 'Caption',
                'instructions' => 'Optional caption rendered below the gallery.',
                'rows' => 2,
                'new_lines' => 'br',
            ])
            ->addTrueFalse('full_width', [
                'label' => 'Full width',
                'instructions' => 'Stretch the gallery to fill the available width.',
                'default_value' => 1,
                'ui' => 1,
            ])
            ->addTrueFalse('full_height', [
                'label' => 'Full height',
                'instructions' => 'Stretch the gallery to fill the parent\'s height (useful inside a Section group).',
                'default_value' => 0,
                'ui' => 1,
            ]);

        return $fields->build();
    }

    public function assets(array $block): void
    {
        //
    }
}
