<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class ImageStrip extends Block
{
    public $name = 'Image Strip';

    public $description = 'A wide, short band of images from across the portfolio, scrolling sideways. Each image links to its project.';

    public $category = 'theme';

    public $icon = 'images-alt2';

    public $keywords = ['images', 'strip', 'band', 'portfolio', 'scroll'];

    public $post_types = ['page'];

    public $mode = 'preview';

    public $blockVersion = 3;

    public $supports = [
        'align' => true,
        'anchor' => true,
        'mode' => true,
        'multiple' => true,
        'jsx' => false,
        'color' => [
            'background' => false,
            'text' => false,
        ],
        'spacing' => [
            'padding' => false,
            'margin' => true,
        ],
    ];

    /**
     * Editor-preview data. The images themselves are picked by the frontend
     * from the portfolio's project images; here we only describe the block.
     */
    public function with(): array
    {
        return [
            'max_images' => (int) (get_field('max_images') ?: 30),
            'auto_scroll' => get_field('auto_scroll') !== false,
        ];
    }

    public function fields(): array
    {
        $fields = Builder::make('image_strip');
        $fields
            ->addNumber('max_images', [
                'label' => 'Number of images',
                'instructions' => 'How many images to show, drawn from across all projects.',
                'default_value' => 30,
                'min' => 6,
                'max' => 80,
                'step' => 1,
            ])
            ->addTrueFalse('auto_scroll', [
                'label' => 'Drift sideways on its own',
                'instructions' => 'Slowly scrolls until the visitor hovers or touches it. Always off for visitors who ask for reduced motion.',
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
