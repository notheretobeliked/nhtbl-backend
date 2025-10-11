<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\bundle;

/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    bundle('app')->enqueue();
}, 100);

/**
 * Register the theme assets with the block editor.
 *
 * @return void
 */
add_action('enqueue_block_editor_assets', function () {
    bundle('editor')->enqueue();
}, 100);

/**
 * Register ACF options pages at the proper time
 */
add_action('init', function () {
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'Featured Projects',
            'menu_title' => 'Featured Projects',
            'menu_slug'  => 'featured-projects-settings',
            'capability' => 'edit_posts',
            'redirect'   => false,
            'position'   => 20,
        ]);
    }
});

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    /**
     * Disable full-site editing support.
     *
     * @link https://wptavern.com/gutenberg-10-5-embeds-pdfs-adds-verse-block-color-options-and-introduces-new-patterns
     */
    remove_theme_support('block-templates');

    /**
     * Register the navigation menus.
     *
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
    ]);

    /**
     * Disable the default block patterns.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#disabling-the-default-block-patterns
     */
    remove_theme_support('core-block-patterns');

    /**
     * Enable plugins to manage the document title.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
     */
    add_theme_support('title-tag');

    /**
     * Enable post thumbnail support.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support('post-thumbnails');

    /**
     * Remove default WordPress image sizes and add custom ones
     */
    // Remove default sizes
    remove_image_size('thumbnail');
    remove_image_size('medium');
    remove_image_size('medium_large'); 
    remove_image_size('large');

    // Add custom image sizes - width only, proportional height
    add_image_size('thumbnail', 300, 0, false);      // 300px wide, proportional height
    add_image_size('small', 600, 0, false);         // 600px wide, proportional height  
    add_image_size('medium', 900, 0, false);        // 900px wide, proportional height
    add_image_size('medium_large', 1200, 0, false); // 1200px wide, proportional height
    add_image_size('large', 1600, 0, false);        // 1600px wide, proportional height
    add_image_size('x_large', 2400, 0, false);      // 2400px wide, proportional height

    /**
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');


    /**
     * Enable HTML5 markup support.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
     */
    add_theme_support('html5', [
        'caption',
        'comment-form',
        'comment-list',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    /**
     * Enable selective refresh for widgets in customizer.
     *
     * @link https://developer.wordpress.org/reference/functions/add_theme_support/#customize-selective-refresh-widgets
     */
    add_theme_support('customize-selective-refresh-widgets');
}, 20);

/**
 * Make custom image sizes available in admin
 */
add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, [
        'x_large' => __('Extra Large'),
    ]);
});

/**
 * 
 * Hack ACF/Graphql to allow querying 'align' on 'attributes'.
 * 
 */

// Try to intercept before WPGraphQL processes the block type
add_filter('register_block_type_args', function ($args, $name) {
    // Ensure all blocks have consistent align attribute definition
    if (isset($args['supports']['align']) && $args['supports']['align']) {
        $args['attributes']['align'] = [
            'type' => 'string',
            'default' => '',
        ];
    }

    // Also handle blocks that already have align attributes defined
    if (isset($args['attributes']['align'])) {
        $args['attributes']['align'] = [
            'type' => 'string',
            'default' => '',
        ];
    }

    return $args;
}, 20, 2);

// Additional filter to normalize alignment for WPGraphQL schema consistency
add_filter('wpgraphql_block_type_registration', function ($config, $block_type) {
    if (isset($config['attributes']['align'])) {
        $config['attributes']['align']['type'] = 'String';
        $config['attributes']['align']['default'] = '';
    }
    return $config;
}, 10, 2);

// Hook into WPGraphQL to ensure consistent field types across all blocks
add_filter('graphql_register_types', function () {
    // Force all blocks to have nullable String align field
    add_filter('graphql_object_type_field_config', function ($field_config, $type_name, $field_name) {
        if ($field_name === 'align' && strpos($type_name, 'Block') !== false) {
            $field_config['type'] = 'String'; // Ensure it's nullable String, not String!
        }
        return $field_config;
    }, 10, 3);
});

// Additional filter to handle alignment values in GraphQL
add_filter('graphql_resolve_field', function ($result, $source, $args, $context, $info) {
    if ($info->fieldName === 'align' && isset($source['attrs']['align'])) {
        $align = $source['attrs']['align'];

        // Handle direct alignment values
        if (in_array($align, ['full', 'wide', 'left', 'right', 'center'])) {
            return $align;
        }

        // Handle CSS class patterns
        if (is_string($align) && preg_match('/align[_-]?(full|wide|left|right|center)/', $align, $matches)) {
            return $matches[1];
        }

        // Handle className attribute if align is not directly set
        if (empty($align) && isset($source['attrs']['className'])) {
            $className = $source['attrs']['className'];
            if (preg_match('/align[_-]?(full|wide|left|right|center)/', $className, $matches)) {
                return $matches[1];
            }
        }
    }

    return $result;
}, 10, 5);



/**
 * Configure WebP Uploads plugin to generate both WebP and AVIF
 */
add_filter('webp_uploads_upload_image_mime_transforms', function($transforms) {
    // Ensure both WebP and AVIF are generated
    return [
        'image/jpeg' => ['image/webp', 'image/avif'],
        'image/png' => ['image/webp', 'image/avif'],
        'image/gif' => ['image/webp'],
    ];
});

/**
 * Initialize Image Color Analysis
 */
require_once get_template_directory() . '/app/ImageColors.php';

/**
 * Initialize Image Migration Admin Tool
 */
if (is_admin()) {
    require_once get_template_directory() . '/app/Admin/ImageMigration.php';
    require_once get_template_directory() . '/app/Admin/ImageColorBatch.php';
}

/**
 * Register the theme sidebars.
 *
 * @return void
 */
add_action('widgets_init', function () {
    $config = [
        'before_widget' => '<section class="widget %1$s %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h3>',
        'after_title' => '</h3>',
    ];

    register_sidebar([
        'name' => __('Primary', 'sage'),
        'id' => 'sidebar-primary',
    ] + $config);

    register_sidebar([
        'name' => __('Footer', 'sage'),
        'id' => 'sidebar-footer',
    ] + $config);
});

/**
 * Register Survey GraphQL Types and Mutations
 */
add_action('graphql_register_types', function () {
    // Helper function to generate keys
    $generate_survey_key = function ($text, $prefix = '', $max_length = 50) {
        $key = strtolower($text);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = preg_replace('/\s+/', '_', trim($key));
        $key = substr($key, 0, $max_length);
        if ($prefix) {
            $key = $prefix . '_' . $key;
        }
        $key = rtrim($key, '_');
        return $key;
    };

    // Register questionKey field on SurveyQuestions
    register_graphql_field('SurveyQuestions', 'questionKey', [
        'type' => 'String',
        'description' => 'Auto-generated key from question text',
        'resolve' => function ($source, $args, $context, $info) use ($generate_survey_key) {
            // Try both camelCase and snake_case
            $question_text = $source['questionText'] ?? $source['question_text'] ?? null;

            if (!empty($question_text)) {
                // Extract question number from the path
                $path = $info->path ?? [];
                $question_index = null;

                // Find the numeric index in the path
                foreach ($path as $segment) {
                    if (is_numeric($segment)) {
                        $question_index = (int)$segment + 1; // +1 for human-readable numbering
                        break;
                    }
                }

                // Fallback: generate without prefix if we can't find the index
                $prefix = $question_index ? 'q' . $question_index : '';
                return $generate_survey_key($question_text, $prefix);
            }
            return null;
        }
    ]);

    // Register optionValue field on SurveyQuestionsOptions
    register_graphql_field('SurveyQuestionsOptions', 'optionValue', [
        'type' => 'String',
        'description' => 'Auto-generated value from option label',
        'resolve' => function ($source, $args, $context, $info) use ($generate_survey_key) {
            // Try both camelCase and snake_case
            $option_label = $source['optionLabel'] ?? $source['option_label'] ?? null;

            if (!empty($option_label)) {
                return $generate_survey_key($option_label);
            }
            return null;
        }
    ]);

    // Register survey response mutation
    register_graphql_mutation('submitSurveyResponse', [
        'inputFields' => [
            'surveyId' => [
                'type' => ['non_null' => 'ID'],
            ],
            'responses' => [
                'type' => ['list_of' => 'SurveyResponseInput'],
            ],
        ],
        'outputFields' => [
            'success' => ['type' => 'Boolean'],
            'responseId' => ['type' => 'ID'],
        ],
        'mutateAndGetPayload' => function ($input) {
            $response_id = wp_insert_post([
                'post_type' => 'survey_response',  // Changed from 'nhtbl_survey_response'
                'post_status' => 'publish',
                'post_title' => 'Response ' . date('Y-m-d H:i:s'),
            ]);

            if (is_wp_error($response_id)) {
                return ['success' => false, 'responseId' => null];
            }

            update_field('survey_reference', $input['surveyId'], $response_id);

            // Store responses with other_text
            $responses_data = [];
            foreach ($input['responses'] as $response) {
                $responses_data[] = [
                    'question_key' => $response['questionKey'],
                    'answer' => $response['answer'],
                    'other_text' => $response['otherText'] ?? '',
                ];
            }
            update_field('responses', $responses_data, $response_id);
            update_field('submitted_at', current_time('mysql'), $response_id);

            return [
                'success' => true,
                'responseId' => $response_id,
            ];
        },
    ]);

    // Register input type
    register_graphql_input_type('SurveyResponseInput', [
        'fields' => [
            'questionKey' => ['type' => ['non_null' => 'String']],
            'answer' => ['type' => ['non_null' => 'String']],
            'otherText' => ['type' => 'String'],
        ],
    ]);
});
