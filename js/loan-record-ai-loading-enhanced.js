/**
 * @file
 * Enhanced loan record form with AI processing feedback and success states.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Enhanced AI loading with success feedback for loan record forms.
   */
  Drupal.behaviors.loanRecordAiLoadingEnhanced = {
    attach: function (context, settings) {
      
      // Target loan record forms specifically
      const forms = once('ai-loading-enhanced', 'form[data-drupal-selector*="loan-record"]', context);
      
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
              handleEnhancedLoadingState(button, originalText, isInputButton, form);
            }
          });
        });
        
        // Add progress indicator to form
        addProgressIndicator(form);
      });
    }
  };

  /**
   * Handle enhanced loading state with progress indication.
   */
  function handleEnhancedLoadingState(button, originalText, isInputButton, form) {
    // Update progress indicator
    updateProgressIndicator(form, 'processing');
    
    // Prevent double submission
    button.disabled = true;
    
    // Add loading class for CSS styling
    button.classList.add('loan-record-ai-loading');
    
    // Create loading content with estimated time
    const loadingText = Drupal.t('Processing AI Analysis...');
    const spinner = '<span class=\"ai-loading-spinner\" aria-hidden=\"true\"></span>';
    const timeEstimate = '<span class=\"ai-time-estimate\">(~5-10 seconds)</span>';
    
    if (isInputButton) {
      // For input buttons, we can only change the value
      button.value = loadingText;
    } else {
      // For button elements, we can add HTML
      button.innerHTML = spinner + ' ' + loadingText + ' ' + timeEstimate;
    }
    
    // Add ARIA live region for screen readers
    announceToScreenReader(Drupal.t('AI analysis started, please wait while we process your loan record'));
    
    // Add ARIA attributes for accessibility
    button.setAttribute('aria-busy', 'true');
    button.setAttribute('aria-label', Drupal.t('Processing loan record with AI analysis, estimated 5 to 10 seconds'));
    
    // Simulate progress updates (since we can't track real AI progress)
    simulateProgress(form, button, originalText, isInputButton);
  }

  /**
   * Add progress indicator to the form.
   */
  function addProgressIndicator(form) {
    const progressContainer = document.createElement('div');
    progressContainer.className = 'ai-progress-container';
    progressContainer.style.display = 'none';
    progressContainer.innerHTML = `
      <div class="ai-progress-bar">
        <div class="ai-progress-fill"></div>
      </div>
      <div class="ai-progress-text">Initializing AI analysis...</div>
    `;
    
    // Insert after the info message
    const riskInfo = form.querySelector('.messages--info');
    if (riskInfo) {
      riskInfo.parentNode.insertBefore(progressContainer, riskInfo.nextSibling);
    } else {
      form.insertBefore(progressContainer, form.firstChild);
    }
  }

  /**
   * Update progress indicator.
   */
  function updateProgressIndicator(form, status, progress = 0, message = '') {
    const container = form.querySelector('.ai-progress-container');
    const progressBar = form.querySelector('.ai-progress-fill');
    const progressText = form.querySelector('.ai-progress-text');
    
    if (!container) return;
    
    switch (status) {
      case 'processing':
        container.style.display = 'block';
        progressText.textContent = message || 'Processing loan data with AI...';
        break;
        
      case 'progress':
        if (progressBar) {
          progressBar.style.width = progress + '%';
        }
        if (message && progressText) {
          progressText.textContent = message;
        }
        break;
        
      case 'complete':
        if (progressBar) {
          progressBar.style.width = '100%';
        }
        if (progressText) {
          progressText.textContent = 'AI analysis complete!';
        }
        // Hide after a brief delay
        setTimeout(() => {
          container.style.display = 'none';
        }, 2000);
        break;
        
      case 'error':
        container.classList.add('ai-progress-error');
        if (progressText) {
          progressText.textContent = 'AI analysis encountered an issue, using fallback assessment...';
        }
        break;
    }
  }

  /**
   * Simulate progress updates for better UX.
   */
  function simulateProgress(form, button, originalText, isInputButton) {
    const progressSteps = [
      { progress: 20, message: 'Analyzing loan parameters...', delay: 1000 },
      { progress: 40, message: 'Calculating risk factors...', delay: 2000 },
      { progress: 60, message: 'Generating AI assessment...', delay: 3000 },
      { progress: 80, message: 'Finalizing risk summary...', delay: 4000 }
    ];
    
    progressSteps.forEach((step, index) => {
      setTimeout(() => {
        updateProgressIndicator(form, 'progress', step.progress, step.message);
      }, step.delay);
    });
    
    // Set maximum timeout as fallback
    setTimeout(() => {
      if (button.disabled) {
        updateProgressIndicator(form, 'error');
        resetButtonState(button, originalText, isInputButton);
        announceToScreenReader(Drupal.t('Form submission is taking longer than expected. You may try submitting again.'));
      }
    }, 30000);
  }

  /**
   * Reset button to original state.
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

  /**
   * Announce to screen readers using ARIA live region.
   */
  function announceToScreenReader(message) {
    let liveRegion = document.getElementById('ai-announcements');
    
    if (!liveRegion) {
      liveRegion = document.createElement('div');
      liveRegion.id = 'ai-announcements';
      liveRegion.setAttribute('aria-live', 'polite');
      liveRegion.setAttribute('aria-atomic', 'true');
      liveRegion.style.position = 'absolute';
      liveRegion.style.left = '-10000px';
      liveRegion.style.width = '1px';
      liveRegion.style.height = '1px';
      liveRegion.style.overflow = 'hidden';
      document.body.appendChild(liveRegion);
    }
    
    liveRegion.textContent = message;
  }

})(Drupal, drupalSettings, once);
