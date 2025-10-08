<?php

namespace App\Fields;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class SurveyResponse extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $surveyResponse = Builder::make('survey_response');

        $surveyResponse
            ->setLocation('post_type', '==', 'survey_response');

        $surveyResponse
            ->addPostObject('survey_reference', [
                'label' => 'Survey',
                'post_type' => ['survey'],
                'return_format' => 'id',
                'required' => true,
            ])
            ->addDateTimePicker('submitted_at', [
                'label' => 'Submitted At',
                'display_format' => 'd/m/Y g:i a',
                'return_format' => 'Y-m-d H:i:s',
            ])
            ->addRepeater('responses', [
                'label' => 'Survey Responses',
                'button_label' => 'Add Response',
                'layout' => 'table',
            ])
                ->addText('question_key', [
                    'label' => 'Question Key',
                    'required' => true,
                ])
                ->addTextarea('answer', [
                    'label' => 'Answer',
                    'rows' => 2,
                ])
                ->addText('other_text', [
                    'label' => 'Other Response Text',
                    'instructions' => 'Free text when user selects "Other"',
                ])
            ->endRepeater()
            ->addGroup('demographics', [
                'label' => 'Demographics (Optional)',
            ])
                ->addNumber('age', [
                    'label' => 'Age',
                ])
                ->addSelect('gender', [
                    'label' => 'Gender',
                    'choices' => [
                        'male' => 'Male',
                        'female' => 'Female',
                        'nonbinary' => 'Nonbinary',
                        'other' => 'Other / Prefer not to say',
                    ],
                    'allow_null' => true,
                ])
                ->addTrueFalse('lives_near_norwich', [
                    'label' => 'Lives within 25 miles of Norwich',
                    'ui' => true,
                ])
                ->addSelect('education', [
                    'label' => 'Highest Level of Education',
                    'choices' => [
                        'primary' => 'Primary school',
                        'secondary_16' => 'Secondary school up to 16 years',
                        'further' => 'Higher or secondary or further education (A-levels, BTEC, etc.)',
                        'university' => 'College or university',
                        'postgraduate' => 'Post-graduate degree',
                        'prefer_not_say' => 'Prefer not to say',
                    ],
                    'allow_null' => true,
                ])
            ->endGroup();

        return $surveyResponse->build();
    }
}