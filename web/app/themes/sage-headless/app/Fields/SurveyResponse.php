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
                'label' => 'Survey (Page/Post)',
                'instructions' => 'The page or post that contains the survey block',
                'post_type' => ['page', 'post', 'survey'],
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
                ->addTextarea('question_text', [
                    'label' => 'Question Text (as asked)',
                    'instructions' => 'The actual question text when the response was submitted',
                    'rows' => 2,
                ])
                ->addText('answer_key', [
                    'label' => 'Answer Key',
                    'instructions' => 'The option key selected (for multiple choice/likert)',
                ])
                ->addTextarea('answer_text', [
                    'label' => 'Answer Text',
                    'instructions' => 'The actual answer text',
                    'rows' => 2,
                ])
                ->addText('other_text', [
                    'label' => 'Other Response Text',
                    'instructions' => 'Free text when user selects "Other"',
                ])
            ->endRepeater();

        return $surveyResponse->build();
    }
}