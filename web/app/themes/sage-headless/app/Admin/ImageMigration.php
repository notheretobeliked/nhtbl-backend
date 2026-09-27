<?php

namespace App\Admin;

class ImageMigration
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('wp_ajax_project_image_migration_dry_run', [$this, 'handle_dry_run']);
        add_action('wp_ajax_project_image_migration_convert', [$this, 'handle_conversion']);
        add_action('wp_ajax_project_image_migration_fix_existing', [$this, 'handle_fix_existing']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_page()
    {
        add_management_page(
            'Project Image Migration',
            'Project Image Migration', 
            'manage_options',
            'project-image-migration',
            [$this, 'render_admin_page']
        );
    }

    public function enqueue_scripts($hook)
    {
        if ($hook !== 'tools_page_project-image-migration') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'projectMigration', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('project_migration_nonce')
        ]);
    }

    public function render_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Project Image Migration Tool</h1>
            <p>This tool will migrate images from the ACF <code>image_gallery</code> field to the post content as Gutenberg image blocks.</p>
            
            <div class="notice notice-warning">
                <p><strong>Important:</strong> This tool will only process posts with empty content (or content containing only empty paragraphs). Posts with existing content will be skipped.</p>
            </div>

            <div class="migration-controls">
                <button id="dry-run-btn" class="button button-secondary">Run Dry Run</button>
                <button id="convert-btn" class="button button-primary" disabled>Convert Images</button>
                <button id="fix-existing-btn" class="button button-secondary">Fix Existing Block Errors</button>
            </div>

            <div id="migration-results" style="margin-top: 20px;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            let dryRunData = null;

            $('#dry-run-btn').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('Running...');
                $('#migration-results').html('<p>Analyzing posts...</p>');

                $.ajax({
                    url: projectMigration.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'project_image_migration_dry_run',
                        nonce: projectMigration.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            dryRunData = response.data;
                            displayDryRunResults(response.data);
                            $('#convert-btn').prop('disabled', false);
                        } else {
                            $('#migration-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                        button.prop('disabled', false).text('Run Dry Run');
                    },
                    error: function() {
                        $('#migration-results').html('<div class="notice notice-error"><p>AJAX error occurred</p></div>');
                        button.prop('disabled', false).text('Run Dry Run');
                    }
                });
            });

            $('#convert-btn').on('click', function() {
                if (!dryRunData || !confirm('Are you sure you want to convert ' + dryRunData.convertible_posts.length + ' posts? This action cannot be undone.')) {
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).text('Converting...');
                $('#dry-run-btn').prop('disabled', true);

                $.ajax({
                    url: projectMigration.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'project_image_migration_convert',
                        nonce: projectMigration.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#migration-results').html('<div class="notice notice-success"><p>Successfully converted ' + response.data.converted_count + ' posts!</p></div>');
                            dryRunData = null;
                            $('#convert-btn').prop('disabled', true).text('Convert Images');
                        } else {
                            $('#migration-results').html('<div class="notice notice-error"><p>Error during conversion: ' + response.data + '</p></div>');
                        }
                        $('#dry-run-btn').prop('disabled', false);
                        button.prop('disabled', false).text('Convert Images');
                    },
                    error: function() {
                        $('#migration-results').html('<div class="notice notice-error"><p>AJAX error during conversion</p></div>');
                        $('#dry-run-btn').prop('disabled', false);
                        button.prop('disabled', false).text('Convert Images');
                    }
                });
            });

            $('#fix-existing-btn').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('Fixing...');
                $('#migration-results').html('<p>Fixing existing block schema errors...</p>');

                $.ajax({
                    url: projectMigration.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'project_image_migration_fix_existing',
                        nonce: projectMigration.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#migration-results').html('<div class="notice notice-success"><p>Fixed ' + response.data.fixed_count + ' posts with block schema issues!</p></div>');
                        } else {
                            $('#migration-results').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                        }
                        button.prop('disabled', false).text('Fix Existing Block Errors');
                    },
                    error: function() {
                        $('#migration-results').html('<div class="notice notice-error"><p>AJAX error occurred</p></div>');
                        button.prop('disabled', false).text('Fix Existing Block Errors');
                    }
                });
            });

            function displayDryRunResults(data) {
                let html = '<div class="migration-preview">';
                
                html += '<h3>Analysis Results</h3>';
                html += '<p><strong>Total project posts:</strong> ' + data.total_posts + '</p>';
                html += '<p><strong>Posts with empty content:</strong> ' + data.empty_content_posts + '</p>';
                html += '<p><strong>Posts with existing content (will be skipped):</strong> ' + data.posts_with_content + '</p>';
                html += '<p><strong>Posts that will be converted:</strong> ' + data.convertible_posts.length + '</p>';

                if (data.convertible_posts.length > 0) {
                    html += '<div class="notice notice-info"><p>The following posts will have their gallery images converted to content:</p></div>';
                    html += '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Post ID</th><th>Title</th><th>Images Count</th><th>Action</th></tr></thead>';
                    html += '<tbody>';
                    
                    data.convertible_posts.forEach(function(post) {
                        html += '<tr>';
                        html += '<td>' + post.id + '</td>';
                        html += '<td><a href="' + post.edit_url + '" target="_blank">' + post.title + '</a></td>';
                        html += '<td>' + post.image_count + '</td>';
                        html += '<td>Will convert ' + post.image_count + ' images to content</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                } else {
                    html += '<div class="notice notice-info"><p>No posts found that need conversion.</p></div>';
                }

                html += '</div>';
                $('#migration-results').html(html);
            }
        });
        </script>

        <style>
        .migration-controls {
            margin: 20px 0;
        }
        .migration-controls button {
            margin-right: 10px;
        }
        .migration-preview {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .migration-preview table {
            margin-top: 15px;
        }
        </style>
        <?php
    }

    public function handle_dry_run()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'project_migration_nonce') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $analysis = $this->analyze_projects();
        wp_send_json_success($analysis);
    }

    public function handle_conversion()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'project_migration_nonce') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $result = $this->convert_projects();
        wp_send_json_success($result);
    }

    public function handle_fix_existing()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'project_migration_nonce') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $result = $this->fix_existing_blocks();
        wp_send_json_success($result);
    }

    private function analyze_projects()
    {
        $projects = get_posts([
            'post_type' => 'project',
            'post_status' => 'any',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => 'image_gallery',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);

        $total_posts = count($projects);
        $empty_content_posts = 0;
        $posts_with_content = 0;
        $convertible_posts = [];

        foreach ($projects as $project) {
            $content = trim($project->post_content);
            $is_empty = $this->is_content_empty($content);
            
            if ($is_empty) {
                $empty_content_posts++;
                
                $gallery = get_field('image_gallery', $project->ID);
                if ($gallery && is_array($gallery) && count($gallery) > 0) {
                    $convertible_posts[] = [
                        'id' => $project->ID,
                        'title' => $project->post_title,
                        'image_count' => count($gallery),
                        'edit_url' => get_edit_post_link($project->ID)
                    ];
                }
            } else {
                $posts_with_content++;
            }
        }

        return [
            'total_posts' => $total_posts,
            'empty_content_posts' => $empty_content_posts,
            'posts_with_content' => $posts_with_content,
            'convertible_posts' => $convertible_posts
        ];
    }

    private function convert_projects()
    {
        $analysis = $this->analyze_projects();
        $converted_count = 0;

        foreach ($analysis['convertible_posts'] as $post_data) {
            $post_id = $post_data['id'];
            $gallery = get_field('image_gallery', $post_id);
            
            if ($gallery && is_array($gallery) && count($gallery) > 0) {
                $content = $this->generate_gutenberg_content($gallery);
                
                $updated = wp_update_post([
                    'ID' => $post_id,
                    'post_content' => $content
                ]);

                if ($updated && !is_wp_error($updated)) {
                    $converted_count++;
                    
                    // Optionally remove the ACF gallery field after successful conversion
                    // Uncomment the line below if you want to clear the ACF field after migration
                    // delete_field('image_gallery', $post_id);
                }
            }
        }

        return [
            'converted_count' => $converted_count,
            'total_eligible' => count($analysis['convertible_posts'])
        ];
    }

    private function is_content_empty($content)
    {
        // Remove all whitespace and check if empty
        $cleaned = trim($content);
        
        if (empty($cleaned)) {
            return true;
        }

        // Check if content is just empty paragraph(s)
        $cleaned = preg_replace('/<p[^>]*>[\s\r\n]*<\/p>/', '', $cleaned);
        $cleaned = preg_replace('/\s+/', '', $cleaned);
        
        return empty($cleaned);
    }

    private function generate_gutenberg_content($gallery)
    {
        $blocks = [];
        
        foreach ($gallery as $image) {
            $image_id = is_array($image) ? $image['ID'] : $image;
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            $image_meta = wp_get_attachment_metadata($image_id);
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $caption = wp_get_attachment_caption($image_id);
            
            // Ensure proper data types for block attributes
            $attrs = [
                'id' => (int) $image_id,
                'url' => (string) $image_url
            ];
            
            // Only add alt if it exists and is not empty
            if (!empty($alt_text)) {
                $attrs['alt'] = (string) $alt_text;
            }
            
            // Only add caption if it exists and is not empty
            if (!empty($caption)) {
                $attrs['caption'] = (string) $caption;
            }
            
            // Create the HTML for the image
            $figure_content = sprintf(
                '<img src="%s" alt="%s" class="wp-image-%d"/>',
                esc_url($image_url),
                esc_attr($alt_text ?: ''),
                $image_id
            );
            
            // Add caption if it exists
            if (!empty($caption)) {
                $figure_content .= '<figcaption class="wp-element-caption">' . esc_html($caption) . '</figcaption>';
            }
            
            $innerHTML = '<figure class="wp-block-image">' . $figure_content . '</figure>';
            
            // Create Gutenberg image block with proper structure
            $block = [
                'blockName' => 'core/image',
                'attrs' => $attrs,
                'innerBlocks' => [],
                'innerHTML' => $innerHTML,
                'innerContent' => [$innerHTML]
            ];
            
            $blocks[] = $block;
        }
        
        return serialize_blocks($blocks);
    }

    private function fix_existing_blocks()
    {
        $projects = get_posts([
            'post_type' => 'project',
            'post_status' => 'any',
            'numberposts' => -1
        ]);

        $fixed_count = 0;

        foreach ($projects as $project) {
            $content = $project->post_content;
            
            // Check if post has blocks
            if (has_blocks($content)) {
                $blocks = parse_blocks($content);
                $blocks_modified = false;
                
                foreach ($blocks as &$block) {
                    if ($block['blockName'] === 'core/image' && isset($block['attrs'])) {
                        $modified = false;
                        
                        // Fix caption attribute - ensure it's string or remove if empty
                        if (isset($block['attrs']['caption'])) {
                            if (empty($block['attrs']['caption']) || $block['attrs']['caption'] === '') {
                                unset($block['attrs']['caption']);
                                $modified = true;
                            } else {
                                $block['attrs']['caption'] = (string) $block['attrs']['caption'];
                                $modified = true;
                            }
                        }
                        
                        // Fix alt attribute - ensure it's string or remove if empty
                        if (isset($block['attrs']['alt'])) {
                            if (empty($block['attrs']['alt']) || $block['attrs']['alt'] === '') {
                                unset($block['attrs']['alt']);
                                $modified = true;
                            } else {
                                $block['attrs']['alt'] = (string) $block['attrs']['alt'];
                                $modified = true;
                            }
                        }
                        
                        // Ensure id is integer
                        if (isset($block['attrs']['id'])) {
                            $block['attrs']['id'] = (int) $block['attrs']['id'];
                            $modified = true;
                        }
                        
                        // Ensure url is string
                        if (isset($block['attrs']['url'])) {
                            $block['attrs']['url'] = (string) $block['attrs']['url'];
                            $modified = true;
                        }
                        
                        if ($modified) {
                            $blocks_modified = true;
                        }
                    }
                }
                
                if ($blocks_modified) {
                    $new_content = serialize_blocks($blocks);
                    
                    $updated = wp_update_post([
                        'ID' => $project->ID,
                        'post_content' => $new_content
                    ]);
                    
                    if ($updated && !is_wp_error($updated)) {
                        $fixed_count++;
                    }
                }
            }
        }

        return [
            'fixed_count' => $fixed_count,
            'total_checked' => count($projects)
        ];
    }
}

// Initialize the migration tool
new ImageMigration();
