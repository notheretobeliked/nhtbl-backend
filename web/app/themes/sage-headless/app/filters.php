<?php

/**
 * Theme filters.
 */

namespace App;

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
 * Auto-generate question keys and option values using ACF save_post hook
 * This processes all survey blocks in a post after saving
 */
add_action('acf/save_post', function ($post_id) {
    // Only process if we have ACF functions available
    if (!function_exists('get_field') || !function_exists('update_field')) {
        return;
    }
    
    // Get all field groups for this post
    $field_groups = acf_get_field_groups(['post_id' => $post_id]);
    
    // Initialize global question counter for this save operation
    $global_question_counter = 0;
    
    foreach ($field_groups as $field_group) {
        $fields = acf_get_fields($field_group);
        $global_question_counter = process_fields_for_keys($fields, $post_id, '', $global_question_counter);
    }
}, 20);

/**
 * Recursively process fields to find and update survey block fields
 */
function process_fields_for_keys($fields, $post_id, $parent_key = '', $global_question_counter = 0) {
    if (!$fields) return $global_question_counter;
    
    foreach ($fields as $field) {
        $field_key = $parent_key ? $parent_key . '_' . $field['name'] : $field['name'];
        
        // Check if this is a survey block repeater
        if ($field['type'] === 'repeater' && $field['name'] === 'questions') {
            $global_question_counter = process_questions_repeater($field_key, $post_id, $global_question_counter);
        }
        
        // Recursively process sub-fields
        if (!empty($field['sub_fields'])) {
            $global_question_counter = process_fields_for_keys($field['sub_fields'], $post_id, $field_key, $global_question_counter);
        }
    }
    
    return $global_question_counter;
}

/**
 * Process questions repeater to generate keys with global numbering
 */
function process_questions_repeater($field_key, $post_id, $global_question_counter) {
    $questions = get_field($field_key, $post_id);
    
    if (!$questions || !is_array($questions)) {
        return $global_question_counter;
    }
    
    $updated = false;
    
    foreach ($questions as $question_index => $question) {
        // Generate question key if missing
        if (!empty($question['question_text']) && empty($question['question_key'])) {
            $global_question_counter++;
            $prefix = 'q' . $global_question_counter;
            $questions[$question_index]['question_key'] = generate_unique_key($question['question_text'], $prefix);
            $updated = true;
        } else if (!empty($question['question_key'])) {
            // If question already has a key, still increment counter to maintain sequence
            $global_question_counter++;
        }
        
        // Process options if they exist
        if (!empty($question['options']) && is_array($question['options'])) {
            foreach ($question['options'] as $option_index => $option) {
                if (!empty($option['option_label']) && empty($option['option_value'])) {
                    $questions[$question_index]['options'][$option_index]['option_value'] = generate_unique_key($option['option_label'], '', 30);
                    $updated = true;
                }
            }
        }
    }
    
    // Update the field if any changes were made
    if ($updated) {
        update_field($field_key, $questions, $post_id);
    }
    
    return $global_question_counter;
}

/**
 * Real-time key generation using ACF field update hooks
 * This approach works better with ACF blocks
 */
add_filter('acf/update_value', function ($value, $post_id, $field) {
    // Check if this is a survey block field (more flexible check)
    $is_survey_field = (
        strpos($field['key'], 'field_survey_block') !== false ||
        strpos($field['name'], 'survey_block') !== false ||
        (isset($field['parent']) && strpos($field['parent'], 'survey_block') !== false)
    );
    
    if (!$is_survey_field) {
        return $value;
    }
    
    // Handle question_text fields
    if (strpos($field['name'], 'question_text') !== false && !empty($value)) {
        // Get the field name pattern to find corresponding question_key field
        $field_name = $field['name'];
        $question_key_field = str_replace('question_text', 'question_key', $field_name);
        
        // Extract question number from field name for prefix
        preg_match('/questions_(\d+)_/', $field_name, $matches);
        $question_num = isset($matches[1]) ? (int)$matches[1] + 1 : '';
        $prefix = $question_num ? 'q' . $question_num : '';
        
        // Generate and update the question key
        $generated_key = generate_unique_key($value, $prefix);
        
        // Try immediate update first
        update_field($question_key_field, $generated_key, $post_id);
        
        // Also use delayed update as backup
        add_action('acf/save_post', function($post_id_inner) use ($question_key_field, $generated_key, $post_id) {
            if ($post_id_inner === $post_id) {
                update_field($question_key_field, $generated_key, $post_id);
            }
        }, 25);
    }
    
    // Handle option_label fields
    if (strpos($field['name'], 'option_label') !== false && !empty($value)) {
        $field_name = $field['name'];
        $option_value_field = str_replace('option_label', 'option_value', $field_name);
        
        // Generate option value
        $generated_value = generate_unique_key($value, '', 30);
        
        // Try immediate update first
        update_field($option_value_field, $generated_value, $post_id);
        
        // Also use delayed update as backup
        add_action('acf/save_post', function($post_id_inner) use ($option_value_field, $generated_value, $post_id) {
            if ($post_id_inner === $post_id) {
                update_field($option_value_field, $generated_value, $post_id);
            }
        }, 25);
    }
    
    return $value;
}, 10, 3);

/**
 * JavaScript-based approach for immediate feedback in the admin
 */
add_action('acf/input/admin_footer', function() {
    ?>
    <script type="text/javascript">
    (function($) {
        if (typeof acf === 'undefined') return;
        
        
        // Function to generate key from text
        function generateKey(text, prefix = '', maxLength = 40) {
            let key = text.toLowerCase();
            key = key.replace(/[^a-z0-9\s]/g, '');
            key = key.replace(/\s+/g, '_').trim();
            
            if (prefix) {
                key = prefix + '_' + key;
                maxLength = maxLength - prefix.length - 1;
            }
            
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
        
        // Function to calculate global question number across all survey blocks
        function calculateGlobalQuestionNumber($field) {
            let globalQuestionNumber = 0;
            let foundCurrentField = false;
            
            // Find the current field's question row first
            const $currentQuestionRow = $field.closest('.acf-row');
            
            // Find all question rows across all repeaters on the page
            const $allQuestionRows = $('.acf-field-repeater').find('.acf-row:not(.acf-clone)').filter(function() {
                // Only count rows that have question_text fields (are question rows)
                return $(this).find('[data-name*="question_text"], [name*="question_text"]').length > 0;
            });
            
            // Count through all question rows until we find the current one
            $allQuestionRows.each(function(index) {
                globalQuestionNumber++;
                
                // Check if this is the current field's row
                if ($(this).is($currentQuestionRow)) {
                    foundCurrentField = true;
                    return false; // Break the loop
                }
            });
            
            return globalQuestionNumber || 1; // Default to 1 if calculation fails
        }
        
        // Function to generate key for a field
        function generateKeyForField($field, value, prefix = '', maxLength = 40) {
            if (!value || value.length < 2) return; // Don't generate for very short values
            
            // Find the corresponding key field
            let $keyField;
            if ($field.is('[data-name="question_text"], [name*="question_text"]')) {
                $keyField = $field.closest('.acf-row').find('input[data-name="question_key"]');
                if (!$keyField.length) {
                    $keyField = $field.closest('.acf-row').find('input[name*="question_key"]');
                }
                if (!prefix) {
                    const globalQuestionNumber = calculateGlobalQuestionNumber($field);
                    prefix = 'q' + globalQuestionNumber;
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
                // Only generate if field is completely empty
                if (!currentValue) {
                    const key = generateKey(value, prefix, maxLength);
                    $keyField.val(key);
                }
            }
        }
        
        // Debounced version for input events
        const debouncedGenerateKey = debounce(generateKeyForField, 500);
        
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
            
            // Clear existing options first
            const $existingRows = $optionsRepeater.find('.acf-row:not(.acf-clone)');
            $existingRows.remove();
            
            // Use button clicking approach - more reliable
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
                    }, 200 * (index + 1)); // Stagger the population
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
                const optionKey = generateKey(optionText, '', 30);
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
                
                generateKeyForField($this, value);
            });
            
            // Secondary trigger: debounced input for immediate feedback when pasting
            $(document).on('input.survey-auto-key-debounced', questionSelectors + ', ' + optionSelectors, function() {
                const $this = $(this);
                const value = $this.val().trim();
                
                // Only trigger debounced generation if the value looks like it was pasted (longer than 10 chars)
                if (value.length > 10) {
                    debouncedGenerateKey($this, value);
                }
            });
            
            // Handle question type changes for Likert scale
            $(document).on('change.survey-likert', questionTypeSelectors, function() {
                const $this = $(this);
                const selectedValue = $this.val();
                
                if (selectedValue === 'likert_scale') {
                    const $questionRow = $this.closest('.acf-row');
                    if ($questionRow.length) {
                        populateLikertOptions($questionRow);
                    }
                }
            });
            
            // Also try to catch changes with a more general selector
            $(document).on('change', 'select', function() {
                const $this = $(this);
                const name = $this.attr('name') || $this.attr('data-name') || '';
                
                if (name.includes('question_type') && $this.val() === 'likert_scale') {
                    const $questionRow = $this.closest('.acf-row');
                    if ($questionRow.length) {
                        populateLikertOptions($questionRow);
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
        setInterval(attachHandlers, 2000);
        
    })(jQuery);
    </script>
    <?php
});

/**
 * Generate unique key from text
 */
function generate_unique_key($text, $prefix = '', $max_length = 40) {
    // Convert to lowercase
    $key = strtolower($text);
    
    // Remove special characters, keep alphanumeric and spaces
    $key = preg_replace('/[^a-z0-9\s]/', '', $key);
    
    // Replace spaces with underscores for better compatibility
    $key = preg_replace('/\s+/', '_', trim($key));
    
    // Add prefix if provided
    if ($prefix) {
        $key = $prefix . '_' . $key;
        // Adjust max length to account for prefix
        $max_length = $max_length - strlen($prefix) - 1;
    }
    
    // Limit length
    $key = substr($key, 0, $max_length);
    
    // Remove trailing underscores
    $key = rtrim($key, '_');
    
    // Add a hash suffix for uniqueness (more deterministic than random)
    $hash = substr(md5($text . time()), 0, 4);
    $key = $key . '_' . $hash;
    
    return $key;
}