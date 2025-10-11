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
        
        console.log('Survey block auto-generation script loaded');

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

        // Function to update option values for a question
        function updateOptionValues(questionRow) {
            console.log('🏷️ updateOptionValues called with row:', questionRow);
            
            // Find all option rows for this question - they're in a separate repeater
            const optionRows = questionRow.querySelectorAll('.acf-field-repeater[data-name="options"] .acf-row:not(.acf-clone)');
            console.log('🏷️ Found option rows:', optionRows.length);
            
            optionRows.forEach((optionRow, index) => {
                console.log(`🏷️ Processing option row ${index}:`, optionRow);
                const optionLabelInput = optionRow.querySelector('input[name*="[option_label]"]');
                const optionValueInput = optionRow.querySelector('input[name*="[option_value]"]');
                
                console.log('🏷️ Option label input:', optionLabelInput);
                console.log('🏷️ Option value input:', optionValueInput);
                
                if (optionLabelInput && optionValueInput) {
                    const optionLabel = optionLabelInput.value;
                    console.log('🏷️ Option label value:', optionLabel);
                    console.log('🏷️ Current option value:', optionValueInput.value);
                    
                    if (optionLabel && !optionValueInput.value) {
                        const generatedValue = generateOptionValue(optionLabel);
                        console.log('🏷️ Generated option value:', generatedValue);
                        optionValueInput.value = generatedValue;
                        // Trigger change event to ensure ACF recognizes the change
                        optionValueInput.dispatchEvent(new Event('change', { bubbles: true }));
                        console.log('✅ Option value updated successfully');
                    } else {
                        console.log('⚠️ Skipping option - no label or value already exists');
                    }
                } else {
                    console.log('❌ Missing option inputs');
                }
            });
        }

        // Function to update question key for a question
        function updateQuestionKey(questionRow) {
            console.log('🔑 updateQuestionKey called with row:', questionRow);
            const questionTextInput = questionRow.querySelector('textarea[name*="[question_text]"], input[name*="[question_text]"]');
            const questionKeyInput = questionRow.querySelector('input[name*="[question_key]"]');
            
            console.log('📝 Question text input:', questionTextInput);
            console.log('🔑 Question key input:', questionKeyInput);
            
            if (questionTextInput && questionKeyInput) {
                const questionText = questionTextInput.value;
                console.log('📝 Question text value:', questionText);
                console.log('🔑 Current key value:', questionKeyInput.value);
                
                if (questionText && !questionKeyInput.value) {
                    const generatedKey = generateOptionValue(questionText);
                    console.log('🔑 Generated key:', generatedKey);
                    questionKeyInput.value = generatedKey;
                    // Trigger change event to ensure ACF recognizes the change
                    questionKeyInput.dispatchEvent(new Event('change', { bubbles: true }));
                    console.log('✅ Question key updated successfully');
                } else {
                    console.log('⚠️ Skipping - no text or key already exists');
                }
            } else {
                console.log('❌ Missing required inputs');
            }
        }

        // Function to handle blur events
        function handleBlur(event) {
            console.log('🔍 Blur event triggered', event);
            const input = event.target;
            console.log('📝 Input element:', input);
            console.log('📝 Input name:', input ? input.name : 'no name');
            
            // Ensure we have a valid DOM element
            if (!input || typeof input.closest !== 'function') {
                console.log('❌ Invalid input element or no closest method');
                return;
            }
            
            const questionRow = input.closest('.acf-row[data-name="questions"]');
            console.log('📋 Question row found:', questionRow);
            
            if (!questionRow) {
                console.log('❌ No question row found');
                return;
            }

            // Update question key if this is a question text input
            if (input.name && input.name.includes('question_text')) {
                console.log('🔑 Updating question key for:', input.value);
                updateQuestionKey(questionRow);
            }
            
            // Update option values if this is an option label input
            if (input.name && input.name.includes('option_label')) {
                console.log('🏷️ Updating option values for:', input.value);
                updateOptionValues(questionRow);
            }
        }

        // Function to observe for new survey blocks
        function observeSurveyBlocks() {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            console.log('🔄 New node added:', node.className, node);
                            
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
                                    console.log(`📋 Node matches survey selector: ${selector}`);
                                    surveyBlocks.push(node);
                                }
                            });
                            
                            // Check if the node contains survey blocks
                            if (node.querySelectorAll) {
                                possibleSelectors.forEach(selector => {
                                    const blocks = node.querySelectorAll(selector);
                                    if (blocks.length > 0) {
                                        console.log(`📋 Node contains survey blocks with selector "${selector}":`, blocks.length);
                                        surveyBlocks = [...surveyBlocks, ...blocks];
                                    }
                                });
                            }
                            
                            surveyBlocks.forEach(function(block) {
                                if (block && typeof block.querySelectorAll === 'function') {
                                    console.log('📋 Attaching listeners to new survey block:', block);
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
            console.log('🔗 Attaching event listeners to block:', block);
            
            // Try to attach listeners immediately
            attachListenersToBlock(block);
            
            // Also try again after a short delay in case fields are still loading
            setTimeout(() => {
                console.log('🔄 Retrying to attach listeners after delay...');
                attachListenersToBlock(block);
            }, 500);
            
            // And once more after a longer delay
            setTimeout(() => {
                console.log('🔄 Final retry to attach listeners...');
                attachListenersToBlock(block);
            }, 1500);
        }
        
        // Helper function to actually attach the listeners
        function attachListenersToBlock(block) {
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
                    console.log(`📝 Found ${found.length} inputs with selector: ${selector}`);
                    inputs = [...inputs, ...found];
                }
            });
            
            // Remove duplicates
            inputs = [...new Set(inputs)];
            console.log('📝 Total unique inputs found:', inputs.length);
            
            inputs.forEach((input, index) => {
                console.log(`📝 Processing input ${index}:`, input);
                console.log(`📝 Input name:`, input.name);
                console.log(`📝 Input type:`, input.type);
                console.log(`📝 Input value:`, input.value);
                
                // Ensure it's a valid DOM element
                if (input && typeof input.addEventListener === 'function') {
                    // Remove existing listeners to prevent duplicates
                    input.removeEventListener('blur', handleBlur);
                    // Add blur event listener
                    input.addEventListener('blur', handleBlur);
                    console.log(`✅ Event listener attached to input: ${input.name}`);
                } else {
                    console.log(`❌ Invalid input element:`, input);
                }
            });
        }

        // Initialize existing survey blocks
        function initExistingBlocks() {
            console.log('🔍 Looking for existing survey blocks...');
            
            // Let's check what blocks are actually available
            const allBlocks = document.querySelectorAll('[class*="acf-block"]');
            console.log('📋 All ACF blocks found:', allBlocks.length);
            allBlocks.forEach((block, index) => {
                console.log(`📋 Block ${index}:`, block.className, block);
            });
            
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
                    console.log(`📋 Found blocks with selector "${selector}":`, blocks.length);
                    surveyBlocks = [...surveyBlocks, ...blocks];
                }
            });
            
            console.log('📋 Total survey blocks found:', surveyBlocks.length);
            surveyBlocks.forEach((block, index) => {
                console.log(`📋 Processing existing block ${index}:`, block);
                attachEventListeners(block);
            });
        }

        // Start observing and initialize existing blocks
        console.log('🚀 Starting survey block initialization...');
        observeSurveyBlocks();
        initExistingBlocks();
        console.log('✅ Survey block initialization complete');

        // Also listen for ACF field updates (in case the block is updated dynamically)
        if (typeof acf !== 'undefined' && acf.addAction) {
            console.log('🔗 Setting up ACF field listener...');
            acf.addAction('ready_field', function(field) {
                console.log('🔄 ACF field ready:', field);
                const block = field.closest('.acf-block-survey-block');
                if (block) {
                    console.log('📋 Found survey block in ACF field, attaching listeners...');
                    attachEventListeners(block);
                } else {
                    console.log('❌ No survey block found for ACF field');
                }
            });
        } else {
            console.log('⚠️ ACF not available for field listener');
        }
    }
})();
