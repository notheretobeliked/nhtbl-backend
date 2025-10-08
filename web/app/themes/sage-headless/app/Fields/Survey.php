<?php

namespace App\Fields;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class Survey extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $survey = Builder::make('survey');

        $survey
            ->setLocation('post_type', '==', 'survey');

        $survey
            ->addRepeater('questions', [
                'label' => 'Survey Questions',
                'button_label' => 'Add Question',
                'layout' => 'block',
            ])
                ->addText('question_text', [
                    'label' => 'Question Text',
                    'required' => true,
                ])
                ->addSelect('question_type', [
                    'label' => 'Question Type',
                    'required' => true,
                    'choices' => [
                        'multiple_choice' => 'Multiple Choice',
                        'likert_scale' => 'Likert Scale',
                        'checkbox' => 'Checkbox (Multiple Select)',
                        'text' => 'Text (Short Answer)',
                        'textarea' => 'Textarea (Long Answer)',
                        'number' => 'Number',
                    ],
                    'default_value' => 'multiple_choice',
                ])
                ->addTrueFalse('required', [
                    'label' => 'Required',
                    'default_value' => false,
                    'ui' => true,
                ])
                ->addTrueFalse('allow_other', [
                    'label' => 'Allow "Other" Response',
                    'instructions' => 'Add an "Other (please specify)" option with a text field',
                    'default_value' => false,
                    'ui' => true,
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
                ])
                ->addRepeater('options', [
                    'label' => 'Answer Options',
                    'instructions' => 'Only used for multiple choice, likert scale, and checkbox questions',
                    'button_label' => 'Add Option',
                    'layout' => 'table',
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
                        'required' => true,
                    ])
                ->endRepeater()
            ->endRepeater();

        return $survey->build();
    }
}