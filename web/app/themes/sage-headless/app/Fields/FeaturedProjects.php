<?php

namespace App\Fields;

use Log1x\AcfComposer\Builder;
use Log1x\AcfComposer\Field;

class FeaturedProjects extends Field
{
    /**
     * The field group.
     *
     * @return array
     */
    public function fields()
    {
        $featuredProjects = Builder::make('featured_projects');

        $featuredProjects
            ->setLocation('options_page', '==', 'featured-projects-settings');

        $featuredProjects
            ->addRelationship('featured_projects', [
                'label' => 'Featured Projects',
                'post_type' => ['project'],
                'filters' => ['search'],
                'return_format' => 'object',
                'min' => 0,
                'max' => 10,
            ]);

        return $featuredProjects->build();
    }
}
