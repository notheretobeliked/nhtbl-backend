<?php

namespace App\Admin;

use App\ImageColors;

/**
 * Image Color Batch Processing Admin Page
 */
class ImageColorBatch
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_post_process_image_colors', [$this, 'handle_batch_process']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_page()
    {
        add_management_page(
            'Image Color Analysis',
            'Image Colors',
            'manage_options',
            'image-color-batch',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook)
    {
        if ('tools_page_image-color-batch' !== $hook) {
            return;
        }

        // Add inline styles for the admin page
        wp_add_inline_style('wp-admin', '
            .color-analysis-stats {
                display: flex;
                gap: 20px;
                margin: 20px 0;
            }
            .color-stat-box {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                flex: 1;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .color-stat-number {
                font-size: 32px;
                font-weight: 600;
                color: #2271b1;
                margin: 10px 0;
            }
            .color-stat-label {
                color: #646970;
                font-size: 14px;
            }
            .color-samples {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 30px;
            }
            .color-sample {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                overflow: hidden;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .color-sample-image {
                width: 100%;
                height: 150px;
                object-fit: cover;
            }
            .color-sample-colors {
                padding: 15px;
            }
            .color-sample-color {
                display: flex;
                align-items: center;
                margin: 8px 0;
                font-size: 12px;
                font-family: monospace;
            }
            .color-sample-swatch {
                width: 30px;
                height: 30px;
                border-radius: 4px;
                margin-right: 10px;
                border: 1px solid #ddd;
            }
            .color-sample-palette {
                display: flex;
                gap: 4px;
                margin-top: 10px;
                flex-wrap: wrap;
            }
            .color-sample-palette-swatch {
                width: 20px;
                height: 20px;
                border-radius: 2px;
                border: 1px solid #ddd;
            }
            .progress-bar {
                width: 100%;
                height: 30px;
                background: #f0f0f1;
                border-radius: 4px;
                overflow: hidden;
                margin: 20px 0;
            }
            .progress-bar-fill {
                height: 100%;
                background: #2271b1;
                transition: width 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
            }
        ');
    }

    /**
     * Render the admin page
     */
    public function render_admin_page()
    {
        // Get stats
        $stats = ImageColors::get_analysis_stats();
        $unanalyzed_ids = ImageColors::get_unanalyzed_images();

        // Get some sample analyzed images for preview
        $sample_images = $this->get_sample_analyzed_images(6);

        ?>
        <div class="wrap">
            <h1>Image Color Analysis</h1>
            <p>Analyze images to extract dominant colors for progressive enhancement on the frontend.</p>

            <?php if ($stats['avif_count'] > 0 && !$stats['imagick_available']): ?>
                <div class="notice notice-info">
                    <p>
                        <strong>AVIF Format Detected:</strong> 
                        You have <?php echo number_format($stats['avif_count']); ?> AVIF images. 
                        Color analysis will automatically use WebP or JPEG versions if available.
                        <br>
                        Images without alternative formats will use fallback colors (#cccccc).
                        <br>
                        <em>The WebP Uploads plugin is configured to generate both WebP and AVIF versions for best compatibility.</em>
                    </p>
                </div>
            <?php endif; ?>

            <div class="color-analysis-stats">
                <div class="color-stat-box">
                    <div class="color-stat-label">Total Images</div>
                    <div class="color-stat-number"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="color-stat-box">
                    <div class="color-stat-label">Analyzed</div>
                    <div class="color-stat-number" style="color: #00a32a;"><?php echo number_format($stats['analyzed']); ?></div>
                </div>
                <div class="color-stat-box">
                    <div class="color-stat-label">Pending Analysis</div>
                    <div class="color-stat-number" style="color: #d63638;"><?php echo number_format($stats['unanalyzed']); ?></div>
                </div>
            </div>

            <?php if (isset($_GET['result'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>Batch processing complete!</strong><br>
                        Processed: <?php echo intval($_GET['processed']); ?> | 
                        Skipped: <?php echo intval($_GET['skipped']); ?> | 
                        Failed: <?php echo intval($_GET['failed']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-top: 20px;">
                <h2>Batch Process Images</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="color-analysis-form">
                    <input type="hidden" name="action" value="process_image_colors">
                    <?php wp_nonce_field('process_image_colors', 'color_analysis_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Processing Mode</th>
                            <td>
                                <label>
                                    <input type="radio" name="mode" value="unanalyzed" checked>
                                    <strong>Process Unanalyzed Only</strong> (<?php echo number_format($stats['unanalyzed']); ?> images)
                                </label>
                                <br>
                                <label style="margin-top: 10px; display: inline-block;">
                                    <input type="radio" name="mode" value="all">
                                    <strong>Re-analyze All Images</strong> (<?php echo number_format($stats['total']); ?> images)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Batch Size</th>
                            <td>
                                <input type="number" name="batch_size" value="50" min="1" max="500" class="small-text">
                                <p class="description">Number of images to process per batch. Lower numbers prevent timeouts.</p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Start Batch Processing', 'primary', 'submit', false); ?>
                    <button type="button" class="button" onclick="if(confirm('Are you sure? This will re-analyze ALL images.')) document.querySelector('input[value=all]').click();">
                        Re-analyze All
                    </button>
                </form>
            </div>

            <?php if (!empty($sample_images)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h2>Sample Results</h2>
                    <p>Preview of recently analyzed images showing dominant colors:</p>
                    
                    <div class="color-samples">
                        <?php foreach ($sample_images as $image): ?>
                            <div class="color-sample">
                                <img src="<?php echo esc_url($image['url']); ?>" 
                                     alt="<?php echo esc_attr($image['title']); ?>" 
                                     class="color-sample-image">
                                <div class="color-sample-colors">
                                    <div class="color-sample-color">
                                        <div class="color-sample-swatch" style="background-color: <?php echo esc_attr($image['colors']['primary']); ?>"></div>
                                        <strong>Primary:</strong>&nbsp;<?php echo esc_html($image['colors']['primary']); ?>
                                    </div>
                                    <div class="color-sample-color">
                                        <div class="color-sample-swatch" style="background-color: <?php echo esc_attr($image['colors']['secondary']); ?>"></div>
                                        <strong>Secondary:</strong>&nbsp;<?php echo esc_html($image['colors']['secondary']); ?>
                                    </div>
                                    <?php if (!empty($image['colors']['palette'])): ?>
                                        <div style="margin-top: 10px;">
                                            <strong style="font-size: 11px;">Palette:</strong>
                                            <div class="color-sample-palette">
                                                <?php foreach ($image['colors']['palette'] as $color): ?>
                                                    <div class="color-sample-palette-swatch" 
                                                         style="background-color: <?php echo esc_attr($color); ?>"
                                                         title="<?php echo esc_attr($color); ?>"></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-top: 20px;">
                <h2>GraphQL Usage</h2>
                <p>Once images are analyzed, you can query the colors in GraphQL:</p>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>query {
  mediaItem(id: "123", idType: DATABASE_ID) {
    sourceUrl
    dominantColor      # "#FF5733"
    secondaryColor     # "#33FF57"
    colorPalette       # ["#5733FF", "#FF3357", ...]
  }
}</code></pre>
                <p><strong>Frontend Progressive Enhancement Example:</strong></p>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>&lt;div style="background-color: {image.dominantColor}"&gt;
  &lt;img src="{image.sourceUrl}" alt="..." /&gt;
&lt;/div&gt;</code></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Handle batch processing
     */
    public function handle_batch_process()
    {
        // Verify nonce
        if (!isset($_POST['color_analysis_nonce']) || 
            !wp_verify_nonce($_POST['color_analysis_nonce'], 'process_image_colors')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Get processing mode
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'unanalyzed';
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $force = ($mode === 'all');

        // Get images to process
        if ($force) {
            // Get all images
            global $wpdb;
            $attachment_ids = $wpdb->get_col("
                SELECT ID 
                FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type LIKE 'image/%'
                ORDER BY ID DESC
                LIMIT {$batch_size}
            ");
        } else {
            // Get unanalyzed only
            $attachment_ids = ImageColors::get_unanalyzed_images();
            $attachment_ids = array_slice($attachment_ids, 0, $batch_size);
        }

        // Process the batch
        $results = ImageColors::batch_process($attachment_ids, $force);

        // Redirect back with results
        wp_redirect(add_query_arg([
            'page' => 'image-color-batch',
            'result' => 'success',
            'processed' => $results['processed'],
            'skipped' => $results['skipped'],
            'failed' => $results['failed'],
        ], admin_url('tools.php')));
        exit;
    }

    /**
     * Get sample analyzed images for preview
     */
    private function get_sample_analyzed_images($limit = 6)
    {
        global $wpdb;

        $query = "
            SELECT ID 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            ORDER BY post_modified DESC
            LIMIT {$limit}
        ";

        $attachment_ids = $wpdb->get_col($query);
        $samples = [];

        foreach ($attachment_ids as $attachment_id) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            
            // Only include if analyzed
            if (!isset($metadata['dominant_colors'])) {
                continue;
            }

            $samples[] = [
                'id' => $attachment_id,
                'title' => get_the_title($attachment_id),
                'url' => wp_get_attachment_image_url($attachment_id, 'medium'),
                'colors' => $metadata['dominant_colors'],
            ];
        }

        return $samples;
    }
}

// Initialize if in admin
if (is_admin()) {
    new ImageColorBatch();
}

