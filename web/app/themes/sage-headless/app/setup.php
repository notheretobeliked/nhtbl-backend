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
     * Enable responsive embed support.
     *
     * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
     */
    add_theme_support('responsive-embeds');
    add_theme_support( 'align-wide' );


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
 * 
 * Hack ACF/Graphql to allow querying 'align' on 'attributes'.
 * 
 */

// Try to intercept before WPGraphQL processes the block type
add_filter('register_block_type_args', function($args, $name) {
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
add_filter('wpgraphql_block_type_registration', function($config, $block_type) {
    if (isset($config['attributes']['align'])) {
        $config['attributes']['align']['type'] = 'String';
        $config['attributes']['align']['default'] = '';
    }
    return $config;
}, 10, 2);

// Hook into WPGraphQL to ensure consistent field types across all blocks
add_filter('graphql_register_types', function() {
    // Force all blocks to have nullable String align field
    add_filter('graphql_object_type_field_config', function($field_config, $type_name, $field_name) {
        if ($field_name === 'align' && strpos($type_name, 'Block') !== false) {
            $field_config['type'] = 'String'; // Ensure it's nullable String, not String!
        }
        return $field_config;
    }, 10, 3);
});

// Additional filter to handle alignment values in GraphQL
add_filter('graphql_resolve_field', function($result, $source, $args, $context, $info) {
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
 * Initialize Image Migration Admin Tool
 */
if (is_admin()) {
    require_once get_template_directory() . '/app/Admin/ImageMigration.php';
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
