<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class SubpageNavigation extends Block
{
    public $name = 'SubpageNavigation';

    public $description = 'A navigation of sibling or child pages, with the current page marked active.';

    public $category = 'theme';

    public $icon = 'menu-alt';

    public $keywords = ['nav', 'navigation', 'subpages', 'siblings', 'menu'];

    public $post_types = ['page'];

    public $mode = 'preview';

    public $blockVersion = 3;

    public $supports = [
        'align' => true,
        'anchor' => true,
        'mode' => true,
        'multiple' => false,
        'jsx' => false,
        'color' => [
            'background' => false,
            'text' => false,
        ],
        'spacing' => [
            'padding' => true,
            'margin' => true,
        ],
    ];

    /**
     * Editor-preview data. The links themselves are resolved in GraphQL (the
     * `navItems` field), so here we only need the chosen source for the preview.
     */
    public function with(): array
    {
        return [
            'nav_source' => get_field('nav_source') ?: 'siblings',
        ];
    }

    public function fields(): array
    {
        $fields = Builder::make('subpage_navigation');
        $fields
            ->addRadio('nav_source', [
                'label' => 'Show',
                'instructions' => 'Which pages to list.',
                'choices' => [
                    'siblings' => 'Sibling pages (same parent)',
                    'subpages' => 'Child pages (subpages of this page)',
                ],
                'default_value' => 'siblings',
                'layout' => 'vertical',
            ]);

        return $fields->build();
    }

    public function assets(array $block): void
    {
        //
    }
}
