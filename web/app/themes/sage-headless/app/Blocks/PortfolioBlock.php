<?php

namespace App\Blocks;

use Log1x\AcfComposer\Block;
use Log1x\AcfComposer\Builder;

class PortfolioBlock extends Block
{
    /**
     * The block name.
     *
     * @var string
     */
    public $name = 'Portfolio Block';

    /**
     * The block description.
     *
     * @var string
     */
    public $description = 'A simple Portfolio Block block.';

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
    public $icon = 'editor-ul';

    /**
     * The block keywords.
     *
     * @var array
     */
    public $keywords = [];

    /**
     * The block post type allow list.
     *
     * @var array
     */
    public $post_types = [];

    /**
     * The parent block type allow list.
     *
     * @var array
     */
    public $parent = [];

    /**
     * The ancestor block type allow list.
     *
     * @var array
     */
    public $ancestor = [];

    /**
     * The default block mode.
     *
     * @var string
     */
    public $mode = 'preview';

    /**
     * The default block alignment.
     *
     * @var string
     */
    public $align = '';

    /**
     * The default block text alignment.
     *
     * @var string
     */
    public $align_text = '';

    /**
     * The default block content alignment.
     *
     * @var string
     */
    public $align_content = '';

    /**
     * The supported block features.
     *
     * @var array
     */
    public $supports = [
        'align' => true,
        'align_text' => false,
        'align_content' => false,
        'full_height' => false,
        'anchor' => false,
        'mode' => false,
        'multiple' => true,
        'jsx' => true,
        'color' => [
            'background' => true,
            'text' => true,
            'gradient' => true,
        ],
    ];

    /**
     * The block styles.
     *
     * @var array
     */
    public $styles = [
        [
            'name' => 'light',
            'label' => 'Light',
            'isDefault' => true,
        ],
        [
            'name' => 'dark',
            'label' => 'Dark',
        ],
    ];

    /**
     * The block preview example data.
     *
     * @var array
     */
    public $example = [
        'items' => [['item' => 'Item one'], ['item' => 'Item two'], ['item' => 'Item three']],
    ];

    /**
     * The block template.
     *
     * @var array
     */
    public $template = [
        'core/heading' => ['placeholder' => 'Hello World'],
        'core/paragraph' => ['placeholder' => 'Welcome to the Portfolio Block block.'],
    ];

    /**
     * Data to be passed to the block before rendering.
     *
     * @return array
     */
    public function with()
    {
        try {
            // Get field values with error handling
            $projectSource = get_field('project_source') ?: 'all';
            $displayMode = get_field('display_mode') ?: 'masonry';
            $enableSearch = get_field('enable_search') ?: false;
            $projectsPerPage = get_field('projects_per_page') ?: 0;
            $sortOrder = get_field('sort_order') ?: 'date_desc';
            $selectedService = get_field('selected_service');
            
            // Get portfolio items with error handling
            $portfolioItems = null;
            try {
                $portfolioItems = $this->portfolioItems();
            } catch (Exception $e) {
                error_log('PortfolioBlock portfolioItems error: ' . $e->getMessage());
                $portfolioItems = [];
            }

            return [
                'portfolioItems' => $portfolioItems,
                'projectSource' => $projectSource,
                'displayMode' => $displayMode,
                'enableSearch' => $enableSearch,
                'projectsPerPage' => $projectsPerPage,
                'sortOrder' => $sortOrder,
                'selectedService' => $selectedService,
            ];
        } catch (Exception $e) {
            error_log('PortfolioBlock with() error: ' . $e->getMessage());
            return [
                'portfolioItems' => [],
                'projectSource' => 'all',
                'displayMode' => 'masonry',
                'enableSearch' => false,
                'projectsPerPage' => 0,
                'sortOrder' => 'date_desc',
                'selectedService' => null,
            ];
        }
    }

    /**
     * The block field group.
     *
     * @return array
     */
    public function fields()
    {
        $portfolioBlock = Builder::make('portfolio_block');
        $portfolioBlock
            ->addRadio('project_source', [
                'label' => 'Project Source',
                'instructions' => 'How would you like to select projects?',
                'required' => 1,
                'choices' => [
                    'all' => 'All Projects',
                    'by_service' => 'Projects by Service',
                    'specific' => 'Specific Projects',
                ],
                'default_value' => 'all',
                'layout' => 'vertical',
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addTaxonomy('selected_service', [
                'label' => 'Select Service',
                'instructions' => 'Choose which service to filter projects by',
                'taxonomy' => 'service',
                'field_type' => 'select',
                'allow_null' => 0,
                'add_term' => 0,
                'load_value' => 0,
                'return_format' => 'object',
                'multiple' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'project_source',
                            'operator' => '==',
                            'value' => 'by_service',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addRelationship('specific_projects', [
                'label' => 'Specific Projects',
                'instructions' => 'Choose specific projects to display',
                'post_type' => ['project'],
                'taxonomy' => [],
                'elements' => '',
                'min' => '1',
                'max' => '50',
                'return_format' => 'object',
                'conditional_logic' => [
                    [
                        [
                            'field' => 'project_source',
                            'operator' => '==',
                            'value' => 'specific',
                        ],
                    ],
                ],
            ])
            ->addSelect('display_mode', [
                'label' => 'Display Mode',
                'instructions' => 'How should the projects be displayed?',
                'required' => 1,
                'choices' => [
                    'horizontal_scroll' => 'Horizontally Scrolling',
                    'vertical_list' => 'Vertical List',
                    'masonry' => 'Masonry Grid',
                ],
                'default_value' => 'masonry',
                'allow_null' => 0,
                'multiple' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addTrueFalse('enable_search', [
                'label' => 'Search Box and Mode Selector',
                'instructions' => 'Enable search box and display mode selector for visitors',
                'message' => 'Show search and filter controls',
                'default_value' => 0,
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addNumber('projects_per_page', [
                'label' => 'Projects Per Page',
                'instructions' => 'Number of projects to show (0 for all). Only applies when not using "Specific Projects".',
                'default_value' => 0,
                'min' => 0,
                'max' => 100,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'project_source',
                            'operator' => '!=',
                            'value' => 'specific',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '50',
                ],
            ])
            ->addSelect('sort_order', [
                'label' => 'Sort Order',
                'instructions' => 'How should projects be sorted?',
                'choices' => [
                    'date_desc' => 'Newest First',
                    'date_asc' => 'Oldest First',
                    'title_asc' => 'Title A-Z',
                    'title_desc' => 'Title Z-A',
                    'menu_order' => 'Custom Order',
                ],
                'default_value' => 'date_desc',
                'allow_null' => 0,
                'conditional_logic' => [
                    [
                        [
                            'field' => 'project_source',
                            'operator' => '!=',
                            'value' => 'specific',
                        ],
                    ],
                ],
                'wrapper' => [
                    'width' => '50',
                ],
            ]);

        return $portfolioBlock->build();
    }

    /**
     * Return the portfolio items based on selected options.
     *
     * @return array|null
     */
    public function portfolioItems()
    {
        $project_source = get_field('project_source') ?: 'all';
        $projects = [];

        switch ($project_source) {
            case 'all':
                $projects = $this->getAllProjects();
                break;
            
            case 'by_service':
                $projects = $this->getProjectsByService();
                break;
            
            case 'specific':
                $projects = $this->getSpecificProjects();
                break;
        }

        if (empty($projects)) {
            return null;
        }

        // Convert projects to standardized array format
        $itemsArray = [];
        foreach ($projects as $project) {
            $project_id = is_object($project) ? $project->ID : $project;
            
            // Get project dates
            $start_date = get_field('start_date', $project_id);
            $end_date = get_field('end_date', $project_id);
            
            // Get and process services (only child services)
            $services = wp_get_post_terms($project_id, 'service');
            $service_names = [];
            if ($services && !is_wp_error($services)) {
                foreach ($services as $service) {
                    if (isset($service->parent) && $service->parent !== null && $service->parent !== 0) {
                        $service_names[] = $service->name;
                    }
                }
            }
            
            // Get and process clients
            $clients = wp_get_post_terms($project_id, 'client');
            $client_names = '';
            if ($clients && !is_wp_error($clients)) {
                $client_names = implode(', ', array_map(function($client) {
                    return $client->name;
                }, $clients));
            }
            
            // Get project data
            $itemsArray[] = [
                'id' => $project_id,
                'title' => get_the_title($project_id),
                'excerpt' => get_the_excerpt($project_id),
                'content' => get_the_content(null, false, $project_id),
                'featured_image' => get_the_post_thumbnail($project_id, 'medium'),
                'featured_image_url' => get_the_post_thumbnail_url($project_id, 'medium'),
                'url' => get_the_permalink($project_id),
                'date' => get_the_date('', $project_id),
                'services' => $services,
                'service_names' => $service_names,
                'client' => $clients,
                'client_names' => $client_names,
                'gallery' => get_field('image_gallery', $project_id),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'year_range' => $this->formatYearRange($start_date, $end_date),
            ];
        }

        return $itemsArray;
    }

    /**
     * Get all projects with sorting and pagination.
     *
     * @return array
     */
    private function getAllProjects()
    {
        $sort_order = get_field('sort_order') ?: 'date_desc';
        
        $args = [
            'post_type' => 'project',
            'post_status' => 'publish',
            'posts_per_page' => $this->getPostsPerPage(),
            'orderby' => $this->getOrderBy(),
            'order' => $this->getOrder(),
        ];

        // Add meta query for date sorting
        if (in_array($sort_order, ['date_asc', 'date_desc'])) {
            $args['meta_key'] = 'start_date';
            $args['meta_type'] = 'DATE';
        }

        // Add meta query for menu order if needed
        if ($sort_order === 'menu_order') {
            $args['orderby'] = 'menu_order';
            $args['order'] = 'ASC';
        }

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Get projects filtered by selected service.
     *
     * @return array
     */
    private function getProjectsByService()
    {
        $selected_service = get_field('selected_service');
        
        if (empty($selected_service)) {
            return [];
        }

        $service_id = is_object($selected_service) ? $selected_service->term_id : $selected_service;
        $sort_order = get_field('sort_order') ?: 'date_desc';

        $args = [
            'post_type' => 'project',
            'post_status' => 'publish',
            'posts_per_page' => $this->getPostsPerPage(),
            'orderby' => $this->getOrderBy(),
            'order' => $this->getOrder(),
            'tax_query' => [
                [
                    'taxonomy' => 'service',
                    'field'    => 'term_id',
                    'terms'    => $service_id,
                ],
            ],
        ];

        // Add meta query for date sorting
        if (in_array($sort_order, ['date_asc', 'date_desc'])) {
            $args['meta_key'] = 'start_date';
            $args['meta_type'] = 'DATE';
        }

        // Add meta query for menu order if needed
        if ($sort_order === 'menu_order') {
            $args['orderby'] = 'menu_order';
            $args['order'] = 'ASC';
        }

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Get specifically selected projects.
     *
     * @return array
     */
    private function getSpecificProjects()
    {
        $specific_projects = get_field('specific_projects');
        
        if (empty($specific_projects) || !is_array($specific_projects)) {
            return [];
        }

        // Return projects in the order they were selected
        return $specific_projects;
    }

    /**
     * Get posts per page setting.
     *
     * @return int
     */
    private function getPostsPerPage()
    {
        $posts_per_page = get_field('projects_per_page') ?: 0;
        return $posts_per_page > 0 ? $posts_per_page : -1;
    }

    /**
     * Get orderby parameter based on sort order.
     *
     * @return string|array
     */
    private function getOrderBy()
    {
        $sort_order = get_field('sort_order') ?: 'date_desc';
        
        switch ($sort_order) {
            case 'date_asc':
            case 'date_desc':
                return 'meta_value';
            case 'title_asc':
            case 'title_desc':
                return 'title';
            case 'menu_order':
                return 'menu_order';
            default:
                return 'meta_value';
        }
    }

    /**
     * Get order parameter based on sort order.
     *
     * @return string
     */
    private function getOrder()
    {
        $sort_order = get_field('sort_order') ?: 'date_desc';
        
        switch ($sort_order) {
            case 'date_asc':
            case 'title_asc':
                return 'ASC';
            case 'date_desc':
            case 'title_desc':
            case 'menu_order':
            default:
                return 'DESC';
        }
    }

    /**
     * Format year range using exact logic from Svelte component.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return string
     */
    private function formatYearRange($startDate, $endDate)
    {
        // If both dates are empty, return empty string
        if (!$startDate && !$endDate) {
            return '';
        }

        // If start date is empty but end date exists, return just end year
        if (!$startDate && $endDate) {
            $endYear = date('Y', strtotime($endDate));
            return "({$endYear})";
        }

        // If end date is empty but start date exists, return just start year
        if ($startDate && !$endDate) {
            $startYear = date('Y', strtotime($startDate));
            return "({$startYear} –)";
        }

        // Both dates exist
        if ($startDate && $endDate) {
            $startYear = date('Y', strtotime($startDate));
            $endYear = date('Y', strtotime($endDate));

            if ($startYear === $endYear) {
                return "({$startYear})";
            } else {
                // Use last two digits of end year if different
                $endYearShort = substr($endYear, -2);
                return "({$startYear}–{$endYearShort})";
            }
        }

        return '';
    }

    /**
     * Assets enqueued when rendering the block.
     *
     * @param  array $block
     * @return void
     */
    public function assets($block)
    {
        //
    }
}
