<?php

namespace App\Fields;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class ProjectGallery extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        // In the editor's sidebar, where the dates are easy to find (not below Yoast).
        $projectGallery = Builder::make('project_data', [
            'title' => 'Project dates',
            'position' => 'side',
            // The frontend's queries ask for projectData; without this the name follows the title.
            'graphql_field_name' => 'projectData',
        ]);

        $projectGallery
            ->setLocation('post_type', '==', 'project')
            ->or('post_type', '==', 'service');

        $projectGallery
            ->addDatePicker('start_date')
            ->addDatePicker('end_date');

        return $projectGallery->build();
    }
}
