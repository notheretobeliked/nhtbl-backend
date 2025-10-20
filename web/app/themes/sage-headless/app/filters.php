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
        
        // Function to populate Likert scale options
        function populateLikertOptions($questionRow) {
            const likertOptions = [
                'Strongly disagree',
                'Disagree', 
                'Neither agree nor disagree',
                'Agree',
                'Strongly agree'
            ];
            
            // Find the options repeater
            let $optionsRepeater = $questionRow.find('[data-name="options"]');
            if (!$optionsRepeater.length) {
                $optionsRepeater = $questionRow.find('.acf-field-repeater').filter(function() {
                    return $(this).find('[data-name*="option"]').length > 0;
                });
            }
            
            if (!$optionsRepeater.length) {
                return;
            }
            
            // Check if already populated to avoid duplicate work
            const $existingRows = $optionsRepeater.find('.acf-row:not(.acf-clone)');
            if ($existingRows.length === likertOptions.length) {
                // Check if first row has the expected value
                const firstRowValue = $existingRows.first().find('input[data-name="option_label"], input[name*="option_label"]').val();
                if (firstRowValue === likertOptions[0]) {
                    return; // Already populated correctly
                }
            }
            
            // Clear existing options
            $existingRows.remove();
            
            // Use button clicking approach - back to staggered for ACF validation
            const $addButton = $optionsRepeater.find('.acf-button[data-event="add-row"]');
            if ($addButton.length) {
                likertOptions.forEach(function(optionText, index) {
                    // Click the add button
                    $addButton.trigger('click');
                    
                    // Wait for the row to be created, then populate it
                    setTimeout(function() {
                        const $newRows = $optionsRepeater.find('.acf-row:not(.acf-clone)');
                        const $targetRow = $newRows.eq(index);
                        
                        if ($targetRow.length) {
                            populateOptionRow($targetRow, optionText);
                        }
                    }, 150 * (index + 1)); // Reduced but still staggered for ACF
                });
            }
        }
        
        // Helper function to populate an option row
        function populateOptionRow($row, optionText) {
            // Find and populate the option label
            let $optionLabel = $row.find('input[data-name="option_label"]');
            if (!$optionLabel.length) {
                $optionLabel = $row.find('input[name*="option_label"]');
            }
            
            if ($optionLabel.length) {
                $optionLabel.val(optionText);
                
                // Generate and set the option value key
                const optionKey = generateKey(optionText, 30);
                let $optionValue = $row.find('input[data-name="option_value"]');
                if (!$optionValue.length) {
                    $optionValue = $row.find('input[name*="option_value"]');
                }
                
                if ($optionValue.length) {
                    $optionValue.val(optionKey);
                }
            }
        }
        
        // More robust event handling
        function attachHandlers() {
            const questionSelectors = 'input[data-name="question_text"], textarea[data-name="question_text"], input[name*="question_text"], textarea[name*="question_text"]';
            const optionSelectors = 'input[data-name="option_label"], input[name*="option_label"]';
            const questionTypeSelectors = 'select[data-name="question_type"], select[name*="question_type"]';
            
            // Remove existing handlers
            $(document).off('blur.survey-auto-key focusout.survey-auto-key input.survey-auto-key-debounced change.survey-likert');
            
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
            
            // Throttled Likert population to prevent excessive calls
            const throttledPopulateLikert = debounce(function($questionRow) {
                populateLikertOptions($questionRow);
            }, 300);
            
            // Handle question type changes for Likert scale
            $(document).on('change.survey-likert', questionTypeSelectors, function() {
                const $this = $(this);
                const selectedValue = $this.val();
                
                if (selectedValue === 'likert_scale') {
                    const $questionRow = $this.closest('.acf-row');
                    if ($questionRow.length) {
                        throttledPopulateLikert($questionRow);
                    }
                }
            });
            
            // Also try to catch changes with a more general selector (throttled)
            $(document).on('change', 'select', function() {
                const $this = $(this);
                const name = $this.attr('name') || $this.attr('data-name') || '';
                
                if (name.includes('question_type') && $this.val() === 'likert_scale') {
                    const $questionRow = $this.closest('.acf-row');
                    if ($questionRow.length) {
                        throttledPopulateLikert($questionRow);
                    }
                }
            });
        }
        
        // Initial attachment
        attachHandlers();
        
        // Re-attach when ACF adds new rows
        if (typeof acf !== 'undefined' && acf.addAction) {
            acf.addAction('ready_field', function(field) {
                if (field.get('type') === 'repeater') {
                    attachHandlers();
                }
            });
            
            acf.addAction('append_field', function(field) {
                attachHandlers();
            });
        }
        
        // Fallback: re-attach periodically
        // Reduced frequency fallback re-attachment for better performance
        setInterval(attachHandlers, 5000);
        
    })(jQuery);
    </script>
    <?php
});
