<?php

namespace App;

use ColorThief\ColorThief;

/**
 * Image Color Analysis
 * 
 * Analyzes images to extract dominant colors for progressive enhancement
 */
class ImageColors
{
    /**
     * Current version of the color analysis algorithm
     */
    const VERSION = '1.0';

    /**
     * Initialize hooks
     */
    public static function init()
    {
        // Analyze colors on image upload
        add_action('add_attachment', [__CLASS__, 'analyze_on_upload']);

        // Register GraphQL fields
        add_action('graphql_register_types', [__CLASS__, 'register_graphql_fields']);
    }

    /**
     * Analyze image colors when uploaded
     */
    public static function analyze_on_upload($attachment_id)
    {
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        // Analyze in background to avoid blocking upload
        self::analyze_image($attachment_id);
    }

    /**
     * Analyze an image and store dominant colors
     * 
     * @param int $attachment_id The attachment ID
     * @param bool $force Force re-analysis even if already analyzed
     * @return array|false Color data or false on failure
     */
    public static function analyze_image($attachment_id, $force = false)
    {
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }

        // Get attachment metadata
        $metadata = wp_get_attachment_metadata($attachment_id);

        // Skip if already analyzed (unless forcing)
        if (
            !$force && isset($metadata['dominant_colors']) &&
            isset($metadata['dominant_colors']['version']) &&
            $metadata['dominant_colors']['version'] === self::VERSION
        ) {
            return $metadata['dominant_colors'];
        }

        // Get the image path and mime type
        $file_path = get_attached_file($attachment_id);
        $mime_type = get_post_mime_type($attachment_id);

        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        // Check if the image format is supported by GD
        // AVIF, HEIC, and some other formats require Imagick
        $unsupported_formats = ['image/avif', 'image/heic', 'image/heif'];
        if (in_array($mime_type, $unsupported_formats)) {
            // Try to find a WebP or JPEG version to analyze instead
            $alternative_path = self::find_alternative_format($attachment_id, $metadata);

            if ($alternative_path) {
                // Use the alternative format for analysis
                $file_path = $alternative_path;
            } elseif (!extension_loaded('imagick')) {
                // No alternative and no Imagick - can't analyze
                $metadata['dominant_colors'] = [
                    'primary' => '#cccccc',
                    'secondary' => '#999999',
                    'palette' => [],
                    'version' => self::VERSION,
                    'analyzed_at' => current_time('mysql'),
                    'error' => 'unsupported_format',
                    'format' => $mime_type,
                    'note' => 'AVIF/HEIC formats require Imagick extension or WebP alternative'
                ];
                wp_update_attachment_metadata($attachment_id, $metadata);
                return false;
            }
        }

        try {
            // Get dominant color (most dominant)
            // ColorThief::getColor($sourceImage, $quality = 10, $area = null, $outputFormat = 'array')
            $primary_rgb = ColorThief::getColor($file_path, 10, null, 'array');
            $primary_hex = self::rgb_to_hex($primary_rgb);

            // Get color palette (10 colors total - we'll use top 10)
            // ColorThief::getPalette($sourceImage, $colorCount = 10, $quality = 10, $area = null, $outputFormat = 'array')
            $palette_rgb = ColorThief::getPalette($file_path, 10, 10, null, 'array');

            // Convert palette to hex
            $palette_hex = array_map([__CLASS__, 'rgb_to_hex'], $palette_rgb);

            // Filter out colors that are too light or too dark (bad for loading states)
            $palette_hex = array_filter($palette_hex, function ($hex) {
                $lightness = self::get_lightness($hex);
                // Keep colors between 15% and 90% lightness
                return $lightness >= 15 && $lightness <= 90;
            });

            // Re-index array after filtering
            $palette_hex = array_values($palette_hex);

            // Ensure we have at least some colors
            if (empty($palette_hex)) {
                // Fallback to unfiltered palette if filtering removed everything
                $palette_hex = array_map([__CLASS__, 'rgb_to_hex'], $palette_rgb);
            }

            // Secondary color is the second in palette (if available)
            $secondary_hex = isset($palette_hex[0]) ? $palette_hex[0] : $primary_hex;

            // If primary and secondary are the same, try next color
            if ($secondary_hex === $primary_hex && isset($palette_hex[1])) {
                $secondary_hex = $palette_hex[1];
            }

            // Get remaining 8 colors for palette (excluding primary and secondary)
            $palette_colors = array_filter($palette_hex, function ($color) use ($primary_hex, $secondary_hex) {
                return $color !== $primary_hex && $color !== $secondary_hex;
            });
            $palette_colors = array_values($palette_colors);
            $palette_colors = array_slice($palette_colors, 0, 8);

            // Store color data
            $color_data = [
                'primary' => $primary_hex,
                'secondary' => $secondary_hex,
                'palette' => $palette_colors,
                'version' => self::VERSION,
                'analyzed_at' => current_time('mysql'),
            ];

            // Update metadata
            $metadata['dominant_colors'] = $color_data;
            wp_update_attachment_metadata($attachment_id, $metadata);

            return $color_data;
        } catch (\Exception $e) {
            // Log error but don't fail
            error_log('Color analysis failed for attachment ' . $attachment_id . ': ' . $e->getMessage());

            // Store a fallback
            $fallback_data = [
                'primary' => '#cccccc',
                'secondary' => '#999999',
                'palette' => [],
                'version' => self::VERSION,
                'analyzed_at' => current_time('mysql'),
                'error' => $e->getMessage(),
            ];

            $metadata['dominant_colors'] = $fallback_data;
            wp_update_attachment_metadata($attachment_id, $metadata);

            return false;
        }
    }

    /**
     * Find an alternative format (WebP or JPEG) to analyze when AVIF is not supported
     * 
     * @param int $attachment_id The attachment ID
     * @param array $metadata The attachment metadata
     * @return string|false Path to alternative format or false if not found
     */
    private static function find_alternative_format($attachment_id, $metadata)
    {
        $upload_dir = wp_upload_dir();
        $file_path = get_attached_file($attachment_id);
        $file_dir = dirname($file_path);
        $file_name = basename($file_path);
        $file_name_without_ext = pathinfo($file_name, PATHINFO_FILENAME);

        // Check if metadata has 'sources' (from WebP Uploads plugin)
        if (isset($metadata['sources'])) {
            // Look for webp version first
            if (isset($metadata['sources']['image/webp']['file'])) {
                $webp_path = $file_dir . '/' . $metadata['sources']['image/webp']['file'];
                if (file_exists($webp_path)) {
                    return $webp_path;
                }
            }

            // Look for jpeg version
            if (isset($metadata['sources']['image/jpeg']['file'])) {
                $jpeg_path = $file_dir . '/' . $metadata['sources']['image/jpeg']['file'];
                if (file_exists($jpeg_path)) {
                    return $jpeg_path;
                }
            }
        }

        // Try to find files with same name but different extensions
        $extensions = ['webp', 'jpg', 'jpeg', 'png'];
        foreach ($extensions as $ext) {
            $alternative_path = $file_dir . '/' . $file_name_without_ext . '.' . $ext;
            if (file_exists($alternative_path)) {
                return $alternative_path;
            }
        }

        return false;
    }

    /**
     * Convert RGB array to hex string
     */
    private static function rgb_to_hex($rgb)
    {
        return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
    }

    /**
     * Get lightness percentage of a hex color
     */
    private static function get_lightness($hex)
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Calculate lightness (average of max and min RGB values)
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;

        return $lightness * 100;
    }

    /**
     * Register GraphQL fields for color data
     */
    public static function register_graphql_fields()
    {
        // Register dominantColor field
        register_graphql_field('MediaItem', 'dominantColor', [
            'type' => 'String',
            'description' => 'Primary dominant color of the image (hex format)',
            'resolve' => function ($source) {
                $attachment_id = $source->ID;
                $metadata = wp_get_attachment_metadata($attachment_id);

                if (isset($metadata['dominant_colors']['primary'])) {
                    return $metadata['dominant_colors']['primary'];
                }

                // Try to analyze if not already done
                $color_data = self::analyze_image($attachment_id);
                return $color_data ? $color_data['primary'] : null;
            }
        ]);

        // Register secondaryColor field
        register_graphql_field('MediaItem', 'secondaryColor', [
            'type' => 'String',
            'description' => 'Secondary dominant color of the image (hex format)',
            'resolve' => function ($source) {
                $attachment_id = $source->ID;
                $metadata = wp_get_attachment_metadata($attachment_id);

                if (isset($metadata['dominant_colors']['secondary'])) {
                    return $metadata['dominant_colors']['secondary'];
                }

                // Try to analyze if not already done
                $color_data = self::analyze_image($attachment_id);
                return $color_data ? $color_data['secondary'] : null;
            }
        ]);

        // Register colorPalette field
        register_graphql_field('MediaItem', 'colorPalette', [
            'type' => ['list_of' => 'String'],
            'description' => 'Array of 8 dominant colors from the image (hex format)',
            'resolve' => function ($source) {
                $attachment_id = $source->ID;
                $metadata = wp_get_attachment_metadata($attachment_id);

                if (isset($metadata['dominant_colors']['palette'])) {
                    return $metadata['dominant_colors']['palette'];
                }

                // Try to analyze if not already done
                $color_data = self::analyze_image($attachment_id);
                return $color_data ? $color_data['palette'] : [];
            }
        ]);

        // Register color fields on CoreImage blocks
        register_graphql_field('CoreImage', 'dominantColor', [
            'type' => 'String',
            'description' => 'Primary dominant color of the image (hex format)',
            'resolve' => function ($block) {
                return self::get_color_from_block($block, 'primary');
            }
        ]);

        register_graphql_field('CoreImage', 'secondaryColor', [
            'type' => 'String',
            'description' => 'Secondary dominant color of the image (hex format)',
            'resolve' => function ($block) {
                return self::get_color_from_block($block, 'secondary');
            }
        ]);

        register_graphql_field('CoreImage', 'colorPalette', [
            'type' => ['list_of' => 'String'],
            'description' => 'Array of 8 dominant colors from the image (hex format)',
            'resolve' => function ($block) {
                $palette = self::get_color_from_block($block, 'palette');
                return $palette ?: [];
            }
        ]);

        register_graphql_field('CoreImage', 'altText', [
            'type' => 'String',
            'description' => 'Alt text for the image',
            'resolve' => function ($block) {
                return $block['attributes']['alt'] ?? '';
            }
        ]);
    }
    // Helper function to extract colors from CoreImage block
    private static function get_color_from_block($block, $color_type)
    {
        // Get image source from block attributes
        $src = $block['attributes']['src'] ?? null;
        if (!$src) {
            return null;
        }

        // Get attachment ID from URL
        $attachment_id = attachment_url_to_postid($src);
        if (!$attachment_id) {
            return null;
        }

        // Get metadata
        $metadata = wp_get_attachment_metadata($attachment_id);

        // Check if colors exist
        if (isset($metadata['dominant_colors'][$color_type])) {
            return $metadata['dominant_colors'][$color_type];
        }

        // Try to analyze if not already done
        $color_data = self::analyze_image($attachment_id);
        return $color_data ? $color_data[$color_type] : null;
    }

    /**
     * Batch process images
     * 
     * @param array $attachment_ids Array of attachment IDs to process
     * @param bool $force Force re-analysis
     * @return array Results of processing
     */
    public static function batch_process($attachment_ids, $force = false)
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'total' => count($attachment_ids),
        ];

        foreach ($attachment_ids as $attachment_id) {
            // Skip if not an image
            if (!wp_attachment_is_image($attachment_id)) {
                $results['skipped']++;
                continue;
            }

            // Check if already analyzed
            if (!$force) {
                $metadata = wp_get_attachment_metadata($attachment_id);
                if (
                    isset($metadata['dominant_colors']['version']) &&
                    $metadata['dominant_colors']['version'] === self::VERSION
                ) {
                    $results['skipped']++;
                    continue;
                }
            }

            // Analyze
            $result = self::analyze_image($attachment_id, $force);

            if ($result) {
                $results['processed']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get all image attachments that need analysis
     * 
     * @return array Array of attachment IDs
     */
    public static function get_unanalyzed_images()
    {
        global $wpdb;

        // Get all image attachments
        $query = "
            SELECT ID 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            ORDER BY ID DESC
        ";

        $attachment_ids = $wpdb->get_col($query);

        // Filter out already analyzed
        $unanalyzed = [];
        foreach ($attachment_ids as $attachment_id) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            if (
                !isset($metadata['dominant_colors']['version']) ||
                $metadata['dominant_colors']['version'] !== self::VERSION
            ) {
                $unanalyzed[] = $attachment_id;
            }
        }

        return $unanalyzed;
    }

    /**
     * Get count of images by analysis status
     * 
     * @return array Counts
     */
    public static function get_analysis_stats()
    {
        global $wpdb;

        // Get total image count
        $total = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");

        // Count AVIF images
        $avif_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type IN ('image/avif', 'image/heic', 'image/heif')
        ");

        $unanalyzed = count(self::get_unanalyzed_images());
        $analyzed = $total - $unanalyzed;

        return [
            'total' => (int) $total,
            'analyzed' => $analyzed,
            'unanalyzed' => $unanalyzed,
            'avif_count' => (int) $avif_count,
            'imagick_available' => extension_loaded('imagick'),
        ];
    }
}

// Initialize
ImageColors::init();
