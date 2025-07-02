/**
 * @file
 * Loan Record form enhancements for AI processing feedback.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Add loading state to loan record forms during AI processing.
   */
  Drupal.behaviors.loanRecordAiLoading = {
    attach: function (context, settings) {
      
      // Target loan record forms specifically
      const forms = once('ai-loading', 'form[data-drupal-selector*="loan-record"]', context);
      
      forms.forEach(function (form) {
        const submitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"]');
        
        submitButtons.forEach(function (button) {
          // Skip delete buttons and other non-save buttons
          if (button.value && (button.value.toLowerCase().includes('delete') || 
              button.value.toLowerCase().includes('cancel'))) {
            return;
          }
          
          // Store original button content
          const originalText = button.value || button.textContent;
          const isInputButton = button.tagName === 'INPUT';
          
          form.addEventListener('submit', function (e) {
            // Only show loading for main save button
            if (e.submitter === button) {
              handleLoadingState(button, originalText, isInputButton);
            }
          });
        });
      });
    }
  };

  /**
   * Handle the loading state for submit buttons.
   */
  function handleLoadingState(button, originalText, isInputButton) {
    // Prevent double submission
    button.disabled = true;
    
    // Add loading class for CSS styling
    button.classList.add('loan-record-ai-loading');
    
    // Create loading content
    const loadingText = Drupal.t('Processing AI Analysis...');
    const spinner = '<span class="ai-loading-spinner" aria-hidden="true"></span>';
    
    if (isInputButton) {
      // For input buttons, we can only change the value
      button.value = loadingText;
    } else {
      // For button elements, we can add HTML
      button.innerHTML = spinner + ' ' + loadingText;
    }
    
    // Add ARIA attributes for accessibility
    button.setAttribute('aria-busy', 'true');
    button.setAttribute('aria-label', Drupal.t('Processing loan record with AI analysis, please wait'));
    
    // Set a timeout as fallback in case form processing takes too long
    setTimeout(function() {
      if (button.disabled) {
        resetButtonState(button, originalText, isInputButton);
        console.warn('Loan record form submission timeout - re-enabling button');
      }
    }, 30000); // 30 second timeout
  }

  /**
   * Reset button to original state (fallback function).
   */
  function resetButtonState(button, originalText, isInputButton) {
    button.disabled = false;
    button.classList.remove('loan-record-ai-loading');
    button.removeAttribute('aria-busy');
    button.removeAttribute('aria-label');
    
    if (isInputButton) {
      button.value = originalText;
    } else {
      button.textContent = originalText;
    }
  }

})(Drupal, drupalSettings, once);
