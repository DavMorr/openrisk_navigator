/**
 * @file
 * Enhanced interactions for entity settings forms.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Enhanced entity settings behaviors.
   */
  Drupal.behaviors.enhancedEntitySettings = {
    attach: function (context, settings) {
      // Add smooth scrolling to quick action links
      once('enhanced-entity-actions', '.quick-action-card a', context).forEach(function (link) {
        link.addEventListener('click', function (e) {
          // Add visual feedback
          this.closest('.quick-action-card').style.transform = 'scale(0.98)';
          setTimeout(() => {
            this.closest('.quick-action-card').style.transform = '';
          }, 150);
        });
      });

      // Auto-refresh statistics
      once('entity-stats-refresh', '.entity-stats', context).forEach(function (statsContainer) {
        // Add refresh functionality if needed
        setInterval(function () {
          // Could implement AJAX refresh of statistics here
        }, 30000); // Every 30 seconds
      });

      // Enhanced form interactions
      once('enhanced-form-details', 'details', context).forEach(function (details) {
        details.addEventListener('toggle', function () {
          if (this.open) {
            // Smooth animation when opening
            const wrapper = this.querySelector('.details-wrapper');
            if (wrapper) {
              wrapper.style.opacity = '0';
              wrapper.style.transform = 'translateY(-10px)';
              setTimeout(() => {
                wrapper.style.transition = 'all 0.3s ease';
                wrapper.style.opacity = '1';
                wrapper.style.transform = 'translateY(0)';
              }, 10);
            }
          }
        });
      });

      // Add loading states for buttons
      once('enhanced-button-loading', '.button', context).forEach(function (button) {
        button.addEventListener('click', function () {
          if (this.type === 'submit') {
            this.classList.add('is-loading');
            this.disabled = true;
            
            // Re-enable after a timeout as fallback
            setTimeout(() => {
              this.classList.remove('is-loading');
              this.disabled = false;
            }, 5000);
          }
        });
      });
    }
  };

  /**
   * Add CSS for loading states.
   */
  Drupal.behaviors.enhancedLoadingStates = {
    attach: function (context, settings) {
      // Add dynamic loading styles
      const style = document.createElement('style');
      style.textContent = `
        .button.is-loading {
          position: relative;
          color: transparent !important;
        }
        .button.is-loading::after {
          content: '';
          position: absolute;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          width: 1rem;
          height: 1rem;
          border: 2px solid currentColor;
          border-top-color: transparent;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }
        @keyframes spin {
          to { transform: translate(-50%, -50%) rotate(360deg); }
        }
      `;
      
      if (!document.querySelector('#enhanced-loading-styles')) {
        style.id = 'enhanced-loading-styles';
        document.head.appendChild(style);
      }
    }
  };

})(jQuery, Drupal, once);
