<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class Slide extends Block
{
    public $name = 'Slide';

    public $description = 'A single slide: an image with an optional rich-text caption.';

    public $category = 'formatting';

    public $icon = 'format-image';

    public $keywords = ['slide', 'image'];

    /**
     * Only insertable inside a Slideshow.
     */
    public $parent = ['acf/slideshow'];

    public $post_types = ['project', 'page', 'post'];

    public $mode = 'preview';

    public $blockVersion = 3;

    public $autoInlineEditing = false;

    public $supports = [
        'align' => false,
        'anchor' => false,
        'mode' => true,
        'multiple' => true,
        'jsx' => false,
        'color' => [
            'background' => false,
            'text' => false,
        ],
        'spacing' => [
            'padding' => false,
            'margin' => false,
        ],
    ];

    /**
     * Editor-preview data. The frontend Svelte component renders the real slide
     * via WPGraphQL; this is preview-only.
     */
    public function with(): array
    {
        return [
            'image' => get_field('image') ?: null,
            'caption' => get_field('caption') ?: '',
        ];
    }

    public function fields(): array
    {
        $fields = Builder::make('slide');
        $fields
            ->addImage('image', [
                'label' => 'Image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'mime_types' => 'jpg, jpeg, png, webp, avif, gif, svg',
            ])
            ->addWysiwyg('caption', [
                'label' => 'Caption',
                'instructions' => 'Optional caption shown over the slide. Rich text — add links to projects here.',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 0,
            ]);

        return $fields->build();
    }

    public function assets(array $block): void
    {
        //
    }
}
