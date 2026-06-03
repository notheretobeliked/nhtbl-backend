<?php

/**
 * Theme setup.
 */

namespace App;

use function Roots\bundle;

/**
 * Disable automatic translation updates. (WordPress.org API HTTP calls are
 * short-circuited in filters.php's pre_http_request handler.)
 */
add_filter('auto_update_translation', '__return_false');
add_filter('translations_api', '__return_false');

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

    // Section behaviour / reveal / parallax extensions on core/group.
    // The attribute schema is registered globally so values serialise in the
    // saved markup regardless of post type; the inspector UI in editor.js is
    // gated to the project (portfolio) CPT.
    if ($name === 'core/group') {
        $args['attributes']['behavior'] = [
            'type' => 'string',
            'default' => 'normal',
        ];
        $args['attributes']['minHeight'] = [
            'type' => 'string',
            'default' => 'auto',
        ];
        $args['attributes']['contentAlign'] = [
            'type' => 'string',
            'default' => 'center',
        ];
        $args['attributes']['reveal'] = [
            'type' => 'string',
            'default' => 'none',
        ];
        $args['attributes']['revealDirection'] = [
            'type' => 'string',
            'default' => 'up',
        ];
        $args['attributes']['revealStagger'] = [
            'type' => 'number',
            'default' => 60,
        ];
        $args['attributes']['parallax'] = [
            'type' => 'boolean',
            'default' => false,
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
    // Ensure both WebP and AVIF are generated. GIFs are intentionally left out:
    // WebP/AVIF generation flattens animated GIFs to a single frame, so we keep
    // GIFs as-is (and serve the original animated file at every size below).
    return [
        'image/jpeg' => ['image/webp', 'image/avif'],
        'image/png' => ['image/webp', 'image/avif'],
    ];
});

/**
 * Animated image support (e.g. an animated featured image: GIF, AVIF or WebP).
 *
 * WordPress resizes only the first frame, so every generated size of an animated
 * source would be static. With Imagick we rebuild each size as a resized animated
 * WebP (frame by frame); without Imagick we fall back to serving the original
 * animated GIF at every size. Either way the size metadata still exists, so the
 * size-based <Image> component keeps working.
 *
 * Note: existing animated images need their thumbnails regenerated to pick this
 * up. is_animated_gif() is only the no-Imagick fallback probe; the Imagick path
 * detects animation generically via Imagick::getNumberImages().
 */
function is_animated_gif($file): bool
{
    if (!is_string($file) || !is_readable($file)) {
        return false;
    }
    $fh = fopen($file, 'rb');
    if (!$fh) {
        return false;
    }
    $frames = 0;
    $chunk = '';
    while (!feof($fh) && $frames < 2) {
        $chunk = substr($chunk, -16) . fread($fh, 1024 * 100);
        $frames += preg_match_all('/\x00\x21\xF9\x04.{4}\x00[\x2C\x21]/s', $chunk);
    }
    fclose($fh);
    return $frames > 1;
}

/**
 * Detect an animated AVIF from its file brand. Needed because ImageMagick 6's
 * libheif can't decode AVIF image sequences — Imagick::getNumberImages() returns
 * 1 for a genuinely animated AVIF — so frame-count detection is unreliable. An
 * animated AVIF declares the "avis" brand in its leading ftyp box.
 */
function is_animated_avif($file): bool
{
    if (!is_string($file) || !is_readable($file)) {
        return false;
    }
    $fh = fopen($file, 'rb');
    if (!$fh) {
        return false;
    }
    $head = fread($fh, 64); // the ftyp box (major + compatible brands) sits here
    fclose($fh);
    return is_string($head) && strpos($head, 'avis') !== false;
}

add_filter('wp_generate_attachment_metadata', function ($metadata, $attachment_id) {
    if (empty($metadata['file']) || empty($metadata['sizes'])) {
        return $metadata;
    }
    // Only formats that can carry animation and that WordPress's resizer flattens
    // to a single frame.
    $mime = get_post_mime_type($attachment_id);
    if (!in_array($mime, ['image/gif', 'image/avif', 'image/webp'], true)) {
        return $metadata;
    }
    $file = get_attached_file($attachment_id);

    // Without Imagick we can only handle GIFs, and only by serving the original
    // at every size (see fallback below).
    if (!class_exists('Imagick')) {
        if ($mime === 'image/gif' && is_animated_gif($file)) {
            $original = wp_basename($metadata['file']);
            foreach ($metadata['sizes'] as $name => $size) {
                $metadata['sizes'][$name]['file'] = $original;
                $metadata['sizes'][$name]['width'] = $metadata['width'];
                $metadata['sizes'][$name]['height'] = $metadata['height'];
                $metadata['sizes'][$name]['mime-type'] = 'image/gif';
                unset($metadata['sizes'][$name]['sources']);
            }
        }
        return $metadata;
    }

    // Is the source actually animated? Imagick reads frame count for any format
    // (GIF / animated AVIF / animated WebP).
    try {
        $probe = new \Imagick($file);
        $animated = $probe->getNumberImages() > 1;
        $probe->clear();
    } catch (\Throwable $e) {
        return $metadata;
    }
    // IM6's libheif can't decode AVIF image sequences (getNumberImages() == 1 for
    // a genuinely animated AVIF). When the brand says it's animated but Imagick
    // can't expand the frames, we can't downscale it — so serve the original
    // animated AVIF at every size. Browsers animate it; it's just not resized.
    // (On IM7 getNumberImages() sees all frames, so this branch never runs and
    // the file gets proper animated-WebP sizes below.)
    if (!$animated && $mime === 'image/avif' && is_animated_avif($file)) {
        $original = wp_basename($metadata['file']);
        foreach ($metadata['sizes'] as $name => $size) {
            $metadata['sizes'][$name]['file'] = $original;
            $metadata['sizes'][$name]['width'] = $metadata['width'];
            $metadata['sizes'][$name]['height'] = $metadata['height'];
            $metadata['sizes'][$name]['mime-type'] = 'image/avif';
            unset($metadata['sizes'][$name]['sources']);
        }
        return $metadata;
    }

    if (!$animated) {
        return $metadata;
    }

    // Regenerate each size as a resized ANIMATED WebP. WordPress only resizes the
    // first frame, so the size files it created are static — we rebuild each one
    // frame by frame. Coalesce ONCE (the costliest step — it expands every frame
    // to full size) and clone per size rather than re-reading + re-coalescing the
    // source for each of the ~6 registered sizes.
    $dir = dirname($file);
    $source = null;
    try {
        $source = (new \Imagick($file))->coalesceImages();
    } catch (\Throwable $e) {
        error_log('Animated image load failed for ' . $file . ': ' . $e->getMessage());
    }
    if ($source) {
        foreach ($metadata['sizes'] as $name => $size) {
            $old_size_file = $dir . '/' . $size['file'];               // the static resize
            $webp_name     = preg_replace('/\.\w+$/', '.webp', $size['file']);
            $webp_path     = $dir . '/' . $webp_name;
            try {
                $img = clone $source;
                foreach ($img as $frame) {
                    // Scales-to-fill + centre-crops — correct for both cropped and
                    // scaled sizes (scaled sizes already share the source aspect,
                    // so nothing is actually cropped).
                    $frame->cropThumbnailImage((int) $size['width'], (int) $size['height']);
                }
                // NB: do NOT deconstructImages() here. It rewrites frames as
                // minimal sub-rectangles (a GIF optimisation); ImageMagick 6's
                // animated-WebP encoder then rejects the odd-sized diff frames
                // ("Invalid frame dimensions"). Keeping the coalesced full-size
                // frames is more compatible — libwebp does its own inter-frame
                // compression regardless.
                $img->setImageFormat('webp');
                $img->setImageIterations(0);                           // loop forever
                $img->setOption('webp:method', '4');                  // 0=fast/larger … 6=slow/smallest
                $img->setImageCompressionQuality(72);
                $img->writeImages($webp_path, true);                   // single animated webp
                $img->clear();

                $metadata['sizes'][$name]['file']      = $webp_name;
                $metadata['sizes'][$name]['mime-type'] = 'image/webp';
                unset($metadata['sizes'][$name]['sources']);
                if ($old_size_file !== $webp_path) {
                    @unlink($old_size_file);                           // drop the static resize
                }
            } catch (\Throwable $e) {
                error_log('Animated image->WebP failed for ' . $webp_path . ': ' . $e->getMessage());
            }
        }
        $source->clear();
    }

    return $metadata;
}, 99, 2);

/**
 * Auto-set the featured image from the first in-content image when a page or
 * project is saved without one (used by the front-end metadata box / listings).
 */
add_action('save_post', function ($post_id, $post) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    if (!in_array($post->post_type, ['page', 'project'], true)) {
        return;
    }
    if (has_post_thumbnail($post_id) || empty($post->post_content)) {
        return;
    }

    $find_image = function ($blocks) use (&$find_image) {
        foreach ($blocks as $block) {
            if (in_array($block['blockName'], ['core/image', 'core/cover'], true) && !empty($block['attrs']['id'])) {
                return (int) $block['attrs']['id'];
            }
            if (!empty($block['innerBlocks'])) {
                $id = $find_image($block['innerBlocks']);
                if ($id) {
                    return $id;
                }
            }
        }
        return 0;
    };

    $image_id = $find_image(parse_blocks($post->post_content));
    if ($image_id && get_post_type($image_id) === 'attachment') {
        set_post_thumbnail($post_id, $image_id);
    }
}, 20, 2);

/**
 * Initialize Image Color Analysis
 */
require_once get_template_directory() . '/app/ImageColors.php';

/**
 * Initialize Admin Tools
 */
if (is_admin()) {
    require_once get_template_directory() . '/app/Admin/ImageMigration.php';
    require_once get_template_directory() . '/app/Admin/ImageColorBatch.php';
    require_once get_template_directory() . '/app/Admin/SurveyExport.php';
    
    // Initialize Survey Export
    new \App\Admin\SurveyExport();
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
    $generate_survey_key = function ($text, $max_length = 50) {
        $key = strtolower($text);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key);
        $key = preg_replace('/\s+/', '_', trim($key));
        $key = substr($key, 0, $max_length);
        $key = rtrim($key, '_');
        return $key;
    };

    // Register questionKey field on SurveyQuestions
    register_graphql_field('SurveyQuestions', 'questionKey', [
        'type' => 'String',
        'description' => 'Question key (uses saved value or auto-generates from question text)',
        'resolve' => function ($source, $args, $context, $info) use ($generate_survey_key) {
            // First, check if question_key already exists (prioritize saved values)
            $question_key = $source['questionKey'] ?? $source['question_key'] ?? null;
            
            if (!empty($question_key)) {
                return $question_key;
            }
            
            // If no saved key, generate from question text (no automatic numbering)
            $question_text = $source['questionText'] ?? $source['question_text'] ?? null;

            if (!empty($question_text)) {
                return $generate_survey_key($question_text);
            }
            return null;
        }
    ]);

    // Register optionValue field on SurveyQuestionsOptions
    register_graphql_field('SurveyQuestionsOptions', 'optionValue', [
        'type' => 'String',
        'description' => 'Option value/key (uses saved value or auto-generates from option label)',
        'resolve' => function ($source, $args, $context, $info) use ($generate_survey_key) {
            // First, check if option_value already exists (e.g., from default Likert options)
            $option_value = $source['optionValue'] ?? $source['option_value'] ?? null;
            
            if (!empty($option_value)) {
                return $option_value;
            }
            
            // If no saved value, generate from option label
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
                'post_type' => 'survey_response',
                'post_status' => 'publish',
                'post_title' => 'Response ' . date('Y-m-d H:i:s'),
            ]);

            if (is_wp_error($response_id)) {
                return ['success' => false, 'responseId' => null];
            }

            // Convert GraphQL ID to WordPress post ID if needed
            $survey_post_id = $input['surveyId'];
            if (strpos($survey_post_id, 'cG9zdDo') === 0) {
                $decoded = base64_decode($survey_post_id);
                if (strpos($decoded, 'post:') === 0) {
                    $survey_post_id = intval(str_replace('post:', '', $decoded));
                }
            }
            
            update_field('survey_reference', $survey_post_id, $response_id);

            // Store responses with all fields
            $responses_data = [];
            foreach ($input['responses'] as $response) {
                $responses_data[] = [
                    'question_key' => $response['questionKey'],
                    'question_text' => $response['questionText'] ?? '',
                    'answer_key' => $response['answerKey'] ?? '',
                    'answer_text' => $response['answerText'] ?? $response['answer'] ?? '',
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
            'questionText' => ['type' => 'String'],
            'answerKey' => ['type' => 'String'],
            'answerText' => ['type' => 'String'],
            'answer' => ['type' => 'String'], // Backward compatibility
            'otherText' => ['type' => 'String'],
        ],
    ]);
});

/**
 * Portfolio authoring UX: a default editor template for new project items and
 * block patterns for adding correctly-configured section blocks.
 */

// New project posts start with the sticky, full-bleed first section + a
// full-width image placeholder, matching the front-end layout. Authors can add
// more sections below it (and the "Portfolio:" patterns make that one click).
add_filter('register_post_type_args', function ($args, $post_type) {
    if ($post_type !== 'project') {
        return $args;
    }

    // Keep the editor fully editable: the template only seeds starter blocks,
    // it must not lock inserting/moving/removing (incl. blocks from patterns).
    $args['template_lock'] = false;

    $args['template'] = [
        // Excerpt, editable inline at the top of the canvas (saves to the post
        // excerpt field that the front-end metadata box reads). Filtered out of
        // the rendered front-end blocks — see the portfolio page loader.
        ['core/post-excerpt'],
        ['core/group', [
            'align'        => 'full',
            'behavior'     => 'stick',
            'minHeight'    => 'screen',
            'contentAlign' => 'stretch',
            'layout'       => ['type' => 'default'],
        ], [
            ['core/image', ['align' => 'full']],
        ]],
        // Trailing empty paragraph so there's an insertion point below the
        // first section (a template ending in a group leaves no appender).
        ['core/paragraph'],
    ];

    return $args;
}, 10, 2);

// Block patterns for adding new portfolio sections, scoped to the project CPT.
add_action('init', function () {
    if (!function_exists('register_block_pattern')) {
        return;
    }

    register_block_pattern_category('nhtbl-portfolio', [
        'label' => __('Portfolio', 'sage'),
    ]);

    // Image section: full-bleed grey section, image at content ("wide") width.
    register_block_pattern('nhtbl/portfolio-image', [
        'title'      => __('Portfolio: Image section', 'sage'),
        'description' => __('Full-bleed grey section with a wide image.', 'sage'),
        'categories' => ['nhtbl-portfolio'],
        'postTypes'  => ['project'],
        'content'    => <<<'HTML'
<!-- wp:group {"align":"full","minHeight":"screen","backgroundColor":"nhtbl-grey-base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-nhtbl-grey-base-background-color has-background"><!-- wp:image {"sizeSlug":"large","align":"wide"} -->
<figure class="wp-block-image alignwide size-large"><img alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
HTML,
    ]);

    // Two-up: black section, text on the left, image on the right.
    register_block_pattern('nhtbl/portfolio-two-up', [
        'title'      => __('Portfolio: Two-up (text left, image right)', 'sage'),
        'description' => __('Full-bleed black section: text column on the left, image on the right.', 'sage'),
        'categories' => ['nhtbl-portfolio'],
        'postTypes'  => ['project'],
        'content'    => <<<'HTML'
<!-- wp:group {"align":"full","minHeight":"screen","backgroundColor":"black","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-white-color has-black-background-color has-text-color has-background"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph -->
<p>Add your text here…</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"large","align":"wide"} -->
<figure class="wp-block-image alignwide size-large"><img alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
HTML,
    ]);
});

