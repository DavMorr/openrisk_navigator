<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Form;

use Drupal\openrisk_navigator\Service\LoanRiskManager;
use Drupal\Core\Entity\ContentEntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for LoanRecord entity edit forms.
 */
final class LoanRecordForm extends ContentEntityForm {

  /**
   * The loan risk manager service.
   */
  protected LoanRiskManager $riskManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->riskManager = $container->get('openrisk_navigator.loan_risk_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    
    // Attach AI loading library for visual feedback
    $form['#attached']['library'][] = 'openrisk_navigator/loan_record_ai_loading';
    
    // Add data attribute to help JavaScript identify loan record forms
    $form['#attributes']['data-drupal-selector'] = 'loan-record-form';
    
    // Add info about AI risk assessment
    $form['risk_info'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">' . 
                   $this->t('AI risk assessment will be automatically generated when you save this loan record. This may take a few seconds.') . 
                   '</div>',
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\openrisk_navigator\Entity\LoanRecord $loan */
    $loan = $this->getEntity();

    // Only generate AI summary for new entities or when key fields change
    $needs_evaluation = $loan->isNew() || $this->hasRiskFieldsChanged($loan);
    
    if ($needs_evaluation) {
      try {
        // Evaluate and store risk summary
        $summary = $this->riskManager->evaluate($loan);
        $loan->set('risk_summary', $summary);
        
        $this->messenger()->addStatus($this->t('AI risk assessment completed successfully.'));
      }
      catch (\Exception $e) {
        $this->messenger()->addWarning($this->t('AI risk assessment failed: @message', ['@message' => $e->getMessage()]));
        
        // Set a basic summary if AI fails
        $ficoScore = $loan->get('fico_score')->value ? (int) $loan->get('fico_score')->value : null;
        $ltvRatio = $loan->get('ltv_ratio')->value ? (float) $loan->get('ltv_ratio')->value : null;
        $riskScore = $this->riskManager->calculateRiskScore($ficoScore, $ltvRatio);
        $loan->set('risk_summary', 'Basic Risk Level: ' . $riskScore['label']);
      }
    }

    // Let the parent handle the actual saving
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the loan record @label.', [
          '@label' => $loan->get('loan_id')->value,
        ]));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Updated the loan record @label.', [
          '@label' => $loan->get('loan_id')->value,
        ]));
        break;
    }

    $form_state->setRedirect('entity.loan_record.canonical', ['loan_record' => $loan->id()]);
    return $status;
  }

  /**
   * Check if risk-relevant fields have changed.
   */
  protected function hasRiskFieldsChanged($loan): bool {
    if ($loan->isNew()) {
      return TRUE;
    }

    $risk_fields = ['fico_score', 'ltv_ratio', 'dti', 'loan_amount', 'defaulted'];
    
    foreach ($risk_fields as $field_name) {
      $original = $loan->original ?? NULL;
      if (!$original) {
        return TRUE; // If no original, assume changed
      }
      
      $current_value = $loan->get($field_name)->value;
      $original_value = $original->get($field_name)->value;
      
      if ($current_value != $original_value) {
        return TRUE;
      }
    }
    
    return FALSE;
  }

}
