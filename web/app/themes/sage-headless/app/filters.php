<?php

/**
 * Theme filters.
 */

 namespace App;

 
/**
 * Disable WordPress.org API calls to prevent SSL errors
 */
add_filter('pre_http_request', function ($preempt, $parsed_args, $url) {
    // Block requests to WordPress.org API endpoints
    if (strpos($url, 'wordpress.org') !== false || 
        strpos($url, 'api.wordpress.org') !== false || 
        strpos($url, 'downloads.wordpress.org') !== false ||
        strpos($url, 's.w.org') !== false ||
        strpos($url, 'wp.org') !== false) {
        error_log('Blocked WordPress.org request: ' . $url);
        return new \WP_Error('http_request_failed', 'WordPress.org API calls disabled to prevent SSL errors.');
    }
    return $preempt;
}, 10, 3);

/**
 * Disable automatic translation updates
 */
add_filter('auto_update_translation', '__return_false');

/**
 * Disable translation API calls
 */
add_filter('translations_api', function() {
    return new \WP_Error('translations_disabled', 'Translation API disabled to prevent SSL errors.');
});


/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Add first image from image gallery as featured image
 * 
 * @return void
 */

add_filter('acf/save_post', function ($post_id) {
	$gallery = get_field('image_gallery', $post_id, false);
	if (!empty($gallery) && !has_post_thumbnail($post_id)) {
		$image_id = $gallery[0];
		set_post_thumbnail($post_id, $image_id);
	}
});

/**
 * Note: Key generation is handled by JavaScript in the admin UI (see below).
 * Keys are generated once when the user types and should never be auto-updated.
 * This ensures user control and prevents unexpected changes.
 */

/**
 * JavaScript-based approach for immediate feedback in the admin
 */
add_action('acf/input/admin_footer', function() {
    ?>
    <script type="text/javascript">
    (function($) {
        if (typeof acf === 'undefined') return;
        
        
        // Function to generate key from text
        function generateKey(text, maxLength = 40) {
            let key = text.toLowerCase();
            key = key.replace(/[^a-z0-9\s]/g, '');
            key = key.replace(/\s+/g, '_').trim();
            
            key = key.substring(0, maxLength);
            key = key.replace(/_+$/, '');
            
            // Add simple hash for uniqueness
            const hash = Math.random().toString(36).substring(2, 6);
            key = key + '_' + hash;
            
            return key;
        }
        
        // Debounce function to prevent excessive key generation
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Function to generate key for a field
        function generateKeyForField($field, value, maxLength = 40, userTriggered = false) {
            if (!value || value.length < 2) return; // Don't generate for very short values
            
            // Find the corresponding key field
            let $keyField;
            if ($field.is('[data-name="question_text"], [name*="question_text"]')) {
                $keyField = $field.closest('.acf-row').find('input[data-name="question_key"]');
                if (!$keyField.length) {
                    $keyField = $field.closest('.acf-row').find('input[name*="question_key"]');
                }
            } else if ($field.is('[data-name="option_label"], [name*="option_label"]')) {
                $keyField = $field.closest('.acf-row').find('input[data-name="option_value"]');
                if (!$keyField.length) {
                    $keyField = $field.closest('.acf-row').find('input[name*="option_value"]');
                }
                maxLength = 30; // Shorter for option values
            }
            
            if ($keyField && $keyField.length) {
                const currentValue = $keyField.val().trim();
                // Only generate if field is completely empty AND this is user-triggered
                if (!currentValue && userTriggered) {
                    const key = generateKey(value, maxLength);
                    $keyField.val(key);
                }
            }
        }
        
        // Debounced version for input events
        const debouncedGenerateKey = debounce(function($field, value, maxLength, userTriggered) {
            generateKeyForField($field, value, maxLength, userTriggered);
        }, 500);
        
        // Event handling for key generation
        function attachHandlers() {
            const questionSelectors = 'input[data-name="question_text"], textarea[data-name="question_text"], input[name*="question_text"], textarea[name*="question_text"]';
            const optionSelectors = 'input[data-name="option_label"], input[name*="option_label"]';
            
            // Remove existing handlers
            $(document).off('blur.survey-auto-key focusout.survey-auto-key input.survey-auto-key-debounced');
            
            // Primary trigger: on blur/focusout (when user leaves the field)
            $(document).on('blur.survey-auto-key focusout.survey-auto-key', questionSelectors + ', ' + optionSelectors, function() {
                const $this = $(this);
                const value = $this.val().trim();
                
                generateKeyForField($this, value, 40, true); // userTriggered = true
            });
            
            // Secondary trigger: debounced input for immediate feedback when pasting
            $(document).on('input.survey-auto-key-debounced', questionSelectors + ', ' + optionSelectors, function() {
                const $this = $(this);
                const value = $this.val().trim();
                
                // Only trigger debounced generation if the value looks like it was pasted (longer than 10 chars)
                if (value.length > 10) {
                    debouncedGenerateKey($this, value, 40, true); // userTriggered = true
                }
            });
        }
        
        // Initial attachment
        attachHandlers();
        
        // Re-attach when ACF adds new rows
        if (typeof acf !== 'undefined' && acf.addAction) {
            acf.addAction('append_field', function(field) {
                // Only re-attach if it's a survey-related field
                if (field.get('name') === 'questions' || field.get('name') === 'options') {
                    attachHandlers();
                }
            });
        }
        
    })(jQuery);
    </script>
    <?php
});
