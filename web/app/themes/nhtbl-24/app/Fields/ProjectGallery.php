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
        $projectGallery = Builder::make('image_gallery');

        $projectGallery
            ->setLocation('post_type', '==', 'project')
            ->or('post_type', '==', 'service');

        $projectGallery
            ->addGallery('image_gallery');

        return $projectGallery->build();
    }
}
