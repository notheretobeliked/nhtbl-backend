<?php

namespace App\Fields;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class PageFields extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $pageFields = Builder::make('background_colour', ['position' => 'side']);

        $pageFields->setLocation('post_type', '==', 'page');

        $pageFields
        ->addSelect('background_colour', [
            'label' => 'Page background colour',
            'required' => 0,
            'choices' => [
                'black' => 'Black',
                'white' => 'White',
                'nhtbl-green-base' => 'Green',
                'nhtbl-purple-base' => 'Purple',
                'nhtbl-purple-light' => 'Light purple',
            ],
            'default_value' => ['white'],
            'allow_null' => 0,
            'ui' => 1,
            'ajax' => 0,
            'return_format' => 'value',
        ])
        ->addTrueFalse('hide`navigation', [
            'label' => 'Hida website navigation?',
            'instructions' => 'Toogle this to hide the website navigation from this page',
            'required' => 0,
            'default_value' => 0,
            'allow_null' => 0,
            'ui' => 1,
            'ajax' => 0,
            'return_format' => 'value',
        ]);
        return $pageFields->build();
    }
}
