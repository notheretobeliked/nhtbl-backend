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
        $projectGallery = Builder::make('project_data');

        $projectGallery
            ->setLocation('post_type', '==', 'project')
            ->or('post_type', '==', 'service');

        $projectGallery
            ->addDatePicker('start_date', [
                'wrapper' => ['width' => '50'],
            ])
            ->addDatePicker('end_date', [
                'wrapper' => ['width' => '50'],
            ])
            ->addGallery('image_gallery');

        return $projectGallery->build();
    }
}
