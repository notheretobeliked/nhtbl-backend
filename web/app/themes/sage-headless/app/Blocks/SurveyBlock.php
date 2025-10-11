<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class SurveyBlock extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Survey Block';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'Add survey questions to any page';

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
    public $icon = 'feedback';

    /**
     * The block keywords.
     *
     * @var array
     */
    public $keywords = ['survey', 'form', 'questions'];

    /**
     * The default block mode.
     *
     * @var string
     */
    public $mode = 'edit';

    /**
     * The supported block features.
     *
     * @var array
     */
    public $supports = [
        'align' => true,
        'mode' => false,
        'multiple' => true,
        'jsx' => true,
    ];

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'questions' => get_field('questions') ?: [],
        ];
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $surveyBlock = Builder::make('survey_block');

        $surveyBlock
            ->addRepeater('questions', [
                'label' => 'Survey Questions',
                'button_label' => 'Add Question',
                'layout' => 'block',
                'min' => 1,
            ])
                ->addText('question_key', [
                    'label' => 'Question Key',
                    'instructions' => 'Auto-generated unique identifier (do not edit)',
                    'readonly' => 1,
                    'disabled' => 1,
                    'wrapper' => [
                        'class' => 'acf-hidden',
                    ],
                ])
                ->addTextarea('question_text', [
                    'label' => 'Question Text',
                    'required' => 1,
                    'rows' => 3,
                ])
                ->addSelect('question_type', [
                    'label' => 'Question Type',
                    'required' => 1,
                    'choices' => [
                        'multiple_choice' => 'Multiple Choice',
                        'likert_scale' => 'Likert Scale',
                        'checkbox' => 'Checkbox (Multiple Select)',
                        'text' => 'Text (Short Answer)',
                        'textarea' => 'Textarea (Long Answer)',
                        'number' => 'Number',
                    ],
                    'default_value' => 'multiple_choice',
                    'wrapper' => [
                        'width' => '50',
                    ],
                ])
                ->addTrueFalse('required', [
                    'label' => 'Required',
                    'default_value' => 0,
                    'ui' => 1,
                    'wrapper' => [
                        'width' => '25',
                    ],
                ])
                ->addTrueFalse('allow_other', [
                    'label' => 'Allow "Other" Response',
                    'instructions' => 'Add an "Other (please specify)" option with a text field',
                    'default_value' => 0,
                    'ui' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'multiple_choice',
                            ],
                        ],
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'checkbox',
                            ],
                        ],
                    ],
                    'wrapper' => [
                        'width' => '25',
                    ],
                ])
                ->addRepeater('options', [
                    'label' => 'Answer Options',
                    'instructions' => 'Only used for multiple choice, likert scale, and checkbox questions',
                    'button_label' => 'Add Option',
                    'layout' => 'table',
                    'min' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'multiple_choice',
                            ],
                        ],
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'likert_scale',
                            ],
                        ],
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'checkbox',
                            ],
                        ],
                    ],
                ])
                    ->addText('option_label', [
                        'label' => 'Option Label',
                        'required' => 1,
                    ])
                    ->addText('option_value', [
                        'label' => 'Option Value',
                        'instructions' => 'Auto-generated (do not edit)',
                        'readonly' => 1,
                        'disabled' => 0,
                    ])
                ->endRepeater()
            ->endRepeater();

        return $surveyBlock->build();
    }

    /**
     * Assets enqueued when rendering the block.
     */
    public function assets($block)
    {
        wp_enqueue_script('survey', \Roots\asset('block-assets/survey.js')->uri(), array(), '', true );
    }
}