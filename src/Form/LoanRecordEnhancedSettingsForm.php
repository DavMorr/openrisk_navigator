<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoanRecordEnhancedSettingsForm.
 *
 * @ingroup openrisk_navigator
 */
class LoanRecordEnhancedSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'loan_record_enhanced_settings';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['enhanced_settings']['#markup'] = 'Enhanced settings for Loan Record. This page has been simplified to prevent conflicts.';
    
    $form['redirect'] = [
      '#type' => 'details',
      '#title' => $this->t('Field Management'),
      '#open' => TRUE,
    ];
    
    $form['redirect']['info'] = [
      '#markup' => '<p>' . $this->t('Please use the standard Field UI interface:') . '</p>'
        . '<ul>'
        . '<li><a href="/admin/structure/loan-record-settings">' . $this->t('Main Settings Page') . '</a></li>'
        . '<li><a href="/admin/structure/loan-record-settings/fields">' . $this->t('Manage Fields') . '</a></li>'
        . '<li><a href="/admin/structure/loan-record-settings/form-display">' . $this->t('Manage Form Display') . '</a></li>'
        . '<li><a href="/admin/structure/loan-record-settings/display">' . $this->t('Manage Display - Configure AI Formatter here!') . '</a></li>'
        . '</ul>',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t('Please use the Field UI links above.'));
  }

}
