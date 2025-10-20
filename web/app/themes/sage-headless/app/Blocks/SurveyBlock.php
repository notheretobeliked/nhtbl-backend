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
        $questions = get_field('questions') ?: [];
        
        // Add default Likert scale options when needed
        foreach ($questions as &$question) {
            if ($question['question_type'] === 'likert_scale' && 
                isset($question['use_default_likert_options']) && 
                $question['use_default_likert_options']) {
                
                $question['options'] = [
                    [
                        'option_label' => 'Strongly Disagree',
                        'option_value' => 'strongly-disagree',
                    ],
                    [
                        'option_label' => 'Disagree',
                        'option_value' => 'disagree',
                    ],
                    [
                        'option_label' => 'Neutral',
                        'option_value' => 'neutral',
                    ],
                    [
                        'option_label' => 'Agree',
                        'option_value' => 'agree',
                    ],
                    [
                        'option_label' => 'Strongly Agree',
                        'option_value' => 'strongly-agree',
                    ],
                ];
            }
        }
        
        return [
            'questions' => $questions,
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
                    'instructions' => 'Auto-generated unique identifier (auto-fills when you type question text, but you can edit it)',
                    'wrapper' => [
                        'width' => '50',
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
                        'likert_scale' => 'Likert Scale',
                        'multiple_choice' => 'Multiple Choice',
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
                ->addTrueFalse('use_default_likert_options', [
                    'label' => 'Use Default Likert Options',
                    'instructions' => 'Use standard Likert scale options (Strongly Disagree to Strongly Agree)',
                    'default_value' => 1,
                    'ui' => 1,
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'likert_scale',
                            ],
                        ],
                    ],
                    'wrapper' => [
                        'width' => '25',
                    ],
                ])
                ->addRepeater('options', [
                    'label' => 'Answer Options',
                    'instructions' => 'Custom options for your question',
                    'button_label' => 'Add Option',
                    'layout' => 'table',
                    'min' => 0,
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
                        [
                            [
                                'field' => 'question_type',
                                'operator' => '==',
                                'value' => 'likert_scale',
                            ],
                            [
                                'field' => 'use_default_likert_options',
                                'operator' => '!=',
                                'value' => '1',
                            ],
                        ],
                    ],
                ])
                    ->addText('option_label', [
                        'label' => 'Option Label',
                    ])
                    ->addText('option_value', [
                        'label' => 'Option Value',
                        'instructions' => 'Auto-generated (auto-fills when you type option label, but you can edit it)',
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