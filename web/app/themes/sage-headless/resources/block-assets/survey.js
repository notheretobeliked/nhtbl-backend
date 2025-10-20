(function() {
    'use strict';

    // Wait for the editor to be ready
    if (typeof wp !== 'undefined' && wp.domReady) {
        wp.domReady(initSurveyBlock);
    } else {
        document.addEventListener('DOMContentLoaded', initSurveyBlock);
    }

    function initSurveyBlock() {
        // Check if we're in the editor
        if (!document.body || !document.body.classList.contains('block-editor-page')) {
            return;
        }

        // Function to convert text to kebab-case
        function toKebabCase(text) {
            return text
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters except spaces and hyphens
                .replace(/\s+/g, '-')         // Replace spaces with hyphens
                .replace(/-+/g, '-')          // Replace multiple hyphens with single hyphen
                .replace(/^-|-$/g, '');       // Remove leading/trailing hyphens
        }

        // Function to generate random 4-digit number
        function generateRandomId() {
            return Math.floor(1000 + Math.random() * 9000);
        }

        // Function to generate option value
        function generateOptionValue(questionText) {
            if (!questionText) return '';
            
            const kebabCase = toKebabCase(questionText);
            const truncated = kebabCase.substring(0, 40);
            const randomId = generateRandomId();
            
            return `${truncated}-${randomId}`;
        }

        // Function to update a single option value (optimized for performance)
        function updateSingleOptionValue(optionRow) {
            const optionLabelInput = optionRow.querySelector('input[name*="[option_label]"]');
            const optionValueInput = optionRow.querySelector('input[name*="[option_value]"]');
            
            if (optionLabelInput && optionValueInput) {
                const optionLabel = optionLabelInput.value;
                const currentValue = optionValueInput.value;
                
                // Only generate if:
                // 1. There's an option label
                // 2. The value field is truly empty
                // 3. The row hasn't been marked as processed
                const isValueEmpty = !currentValue || currentValue.trim() === '';
                const isProcessed = optionRow.dataset.valueProcessed === 'true';
                
                if (optionLabel && isValueEmpty && !isProcessed) {
                    const generatedValue = generateOptionValue(optionLabel);
                    optionValueInput.value = generatedValue;
                    // Mark this row as processed to prevent future regeneration
                    optionRow.dataset.valueProcessed = 'true';
                    // Trigger change event to ensure ACF recognizes the change
                    optionValueInput.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    // If a value already exists, mark the row as processed
                    if (!isValueEmpty) {
                        optionRow.dataset.valueProcessed = 'true';
                    }
                }
            }
        }
        
        // Function to update option values for all options in a question (used on initialization)
        function updateAllOptionValues(questionRow) {
            // Find all option rows for this question - they're in a separate repeater
            const optionRows = questionRow.querySelectorAll('.acf-field-repeater[data-name="options"] .acf-row:not(.acf-clone)');
            
            optionRows.forEach((optionRow) => {
                updateSingleOptionValue(optionRow);
            });
        }

        // Function to update question key for a question
        function updateQuestionKey(questionRow) {
            const questionTextInput = questionRow.querySelector('textarea[name*="[question_text]"], input[name*="[question_text]"]');
            const questionKeyInput = questionRow.querySelector('input[name*="[question_key]"]');
            
            if (questionTextInput && questionKeyInput) {
                const questionText = questionTextInput.value;
                const currentKeyValue = questionKeyInput.value;
                
                // Only generate if: 
                // 1. There's question text
                // 2. The key field is truly empty (not just whitespace)
                // 3. The row hasn't been marked as processed (to prevent re-generation on page load)
                const isKeyEmpty = !currentKeyValue || currentKeyValue.trim() === '';
                const isProcessed = questionRow.dataset.keyProcessed === 'true';
                
                if (questionText && isKeyEmpty && !isProcessed) {
                    const generatedKey = generateOptionValue(questionText);
                    questionKeyInput.value = generatedKey;
                    // Mark this row as processed to prevent future regeneration
                    questionRow.dataset.keyProcessed = 'true';
                    // Trigger change event to ensure ACF recognizes the change
                    questionKeyInput.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    // If a key already exists, mark the row as processed
                    if (!isKeyEmpty) {
                        questionRow.dataset.keyProcessed = 'true';
                    }
                }
            }
        }

        // Function to handle blur events
        function handleBlur(event) {
            const input = event.target;
            
            // Ensure we have a valid DOM element
            if (!input || typeof input.closest !== 'function') {
                return;
            }
            
            const questionRow = input.closest('.acf-row[data-name="questions"]');
            const optionRow = input.closest('.acf-field-repeater[data-name="options"] .acf-row:not(.acf-clone)');
            
            // If user manually edits the question_key field, mark the row as processed
            if (input.name && input.name.includes('question_key') && questionRow) {
                if (input.value && input.value.trim() !== '') {
                    questionRow.dataset.keyProcessed = 'true';
                }
                return;
            }
            
            // If user manually edits the option_value field, mark the row as processed
            if (input.name && input.name.includes('option_value') && optionRow) {
                if (input.value && input.value.trim() !== '') {
                    optionRow.dataset.valueProcessed = 'true';
                }
                return;
            }
            
            // Update question key if this is a question text input
            if (input.name && input.name.includes('question_text') && questionRow) {
                updateQuestionKey(questionRow);
                return;
            }
            
            // Update ONLY this specific option value if this is an option label input
            if (input.name && input.name.includes('option_label') && optionRow) {
                updateSingleOptionValue(optionRow);
                return;
            }
        }

        // Function to observe for new survey blocks
        function observeSurveyBlocks() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if it's a survey block or contains one
                            let surveyBlocks = [];
                            
                            // Check if the node itself is a survey block
                            const possibleSelectors = [
                                '.wp-block-acf-survey-block',
                                '[data-type="acf/survey-block"]',
                                '.acf-block-survey-block',
                                '.acf-block-survey',
                                '.wp-block-acf-survey',
                                '[data-type="acf/survey"]'
                            ];
                            
                            possibleSelectors.forEach(selector => {
                                if (node.matches && node.matches(selector)) {
                                    surveyBlocks.push(node);
                                }
                            });
                            
                            // Check if the node contains survey blocks
                            if (node.querySelectorAll) {
                                possibleSelectors.forEach(selector => {
                                    const blocks = node.querySelectorAll(selector);
                                    if (blocks.length > 0) {
                                        surveyBlocks = [...surveyBlocks, ...blocks];
                                    }
                                });
                            }
                            
                            surveyBlocks.forEach(function(block) {
                                if (block && typeof block.querySelectorAll === 'function') {
                                    attachEventListeners(block);
                                }
                            });
                        }
                    });
                });
            });

            if (document.body) {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        }

        // Function to attach event listeners to survey blocks
        function attachEventListeners(block) {
            // Try to attach listeners immediately
            attachListenersToBlock(block);
            
            // Single retry after a short delay for fields that load dynamically
            setTimeout(() => {
                attachListenersToBlock(block);
            }, 100);
        }
        
        // Helper function to actually attach the listeners
        function attachListenersToBlock(block) {
            // Use requestIdleCallback or setTimeout to avoid blocking ACF's initialization
            const processBlock = () => {
                // First, mark all existing rows with values as processed
                // This prevents regeneration of existing saved data
                const questionRows = block.querySelectorAll('.acf-row[data-name="questions"]:not(.acf-clone)');
                questionRows.forEach(questionRow => {
                    const questionKeyInput = questionRow.querySelector('input[name*="[question_key]"]');
                    if (questionKeyInput && questionKeyInput.value && questionKeyInput.value.trim() !== '') {
                        questionRow.dataset.keyProcessed = 'true';
                    }
                });
                
                // Mark all existing option rows with values as processed
                const optionRows = block.querySelectorAll('.acf-field-repeater[data-name="options"] .acf-row:not(.acf-clone)');
                optionRows.forEach(optionRow => {
                    const optionValueInput = optionRow.querySelector('input[name*="[option_value]"]');
                    if (optionValueInput && optionValueInput.value && optionValueInput.value.trim() !== '') {
                        optionRow.dataset.valueProcessed = 'true';
                    }
                });
                
                // Use a more comprehensive selector to find all possible input fields
                const inputSelectors = [
                    'input[type="text"]',
                    'textarea',
                    'input[type="email"]',
                    'input[type="url"]',
                    'input[type="tel"]',
                    'input[type="search"]',
                    'input[type="number"]',
                    'input[type="password"]',
                    'input:not([type])', // inputs without type attribute (defaults to text)
                    'input[type=""]' // inputs with empty type attribute
                ];
                
                let inputs = [];
                inputSelectors.forEach(selector => {
                    const found = block.querySelectorAll(selector);
                    if (found.length > 0) {
                        inputs = [...inputs, ...found];
                    }
                });
                
                // Remove duplicates
                inputs = [...new Set(inputs)];
                
                inputs.forEach((input) => {
                    // Ensure it's a valid DOM element
                    if (input && typeof input.addEventListener === 'function') {
                        // Check if listener is already attached
                        if (!input.dataset.surveyListenerAttached) {
                            input.addEventListener('blur', handleBlur);
                            input.dataset.surveyListenerAttached = 'true';
                        }
                    }
                });
            };
            
            // Use requestIdleCallback if available, otherwise use setTimeout
            if (window.requestIdleCallback) {
                requestIdleCallback(processBlock, { timeout: 100 });
            } else {
                setTimeout(processBlock, 0);
            }
        }

        // Initialize existing survey blocks
        function initExistingBlocks() {
            // Try different possible class names
            const possibleSelectors = [
                '.wp-block-acf-survey-block',
                '[data-type="acf/survey-block"]',
                '.acf-block-survey-block',
                '.acf-block-survey',
                '.wp-block-acf-survey',
                '[data-type="acf/survey"]'
            ];
            
            let surveyBlocks = [];
            possibleSelectors.forEach(selector => {
                const blocks = document.querySelectorAll(selector);
                if (blocks.length > 0) {
                    surveyBlocks = [...surveyBlocks, ...blocks];
                }
            });
            
            surveyBlocks.forEach((block) => {
                attachEventListeners(block);
            });
        }

        // Start observing and initialize existing blocks
        observeSurveyBlocks();
        initExistingBlocks();

        // Also listen for ACF field updates (in case the block is updated dynamically)
        if (typeof acf !== 'undefined' && acf.addAction) {
            // When a repeater row is added, attach listeners
            acf.addAction('append', function($el) {
                const block = $el.closest('.acf-block-survey-block');
                if (block.length > 0) {
                    // Small delay to let ACF finish rendering the row
                    setTimeout(() => {
                        attachListenersToBlock(block[0]);
                    }, 50);
                }
            });
            
            // When conditional logic shows/hides fields
            acf.addAction('show_field', function(field) {
                const block = field.$el.closest('.acf-block-survey-block');
                if (block.length > 0) {
                    setTimeout(() => {
                        attachListenersToBlock(block[0]);
                    }, 50);
                }
            });
        }
    }
})();
