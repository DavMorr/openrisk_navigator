<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openrisk_navigator\Service\LoanRiskManager;

/**
 * Returns a simple test page for risk evaluation.
 */
/**
 * Controller for testing loan risk evaluation.
 */
class LoanRiskTestController extends ControllerBase {

  /**
   * The loan risk manager service.
   *
   * @var \Drupal\openrisk_navigator\Service\LoanRiskManager
   */
  protected LoanRiskManager $riskManager;

  /**
   * Constructs a LoanRiskTestController object.
   *
   * @param \Drupal\openrisk_navigator\Service\LoanRiskManager $riskManager
   *   The loan risk manager service.
   */
  public function __construct(LoanRiskManager $riskManager) {
    $this->riskManager = $riskManager;
  }

  /**
   * Creates an instance of the controller with dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return self
   *   An instance of LoanRiskTestController.
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('openrisk_navigator.loan_risk_manager')
    );
  }

  /**
   * Evaluates loan risk using different strategies and returns a render array.
   *
   * @return array
   *   A render array containing the evaluation results.
   */
  public function evaluate(): array {
    try {
      // Create a test LoanRecord entity for evaluation
      $testLoan = \Drupal::entityTypeManager()
        ->getStorage('loan_record')
        ->create([
          'loan_id' => 'TEST-' . date('Y-m-d-H-i-s'),
          'borrower_name' => 'Test Borrower',
          'loan_amount' => 250000,
          'fico_score' => 580,
          'ltv_ratio' => 92.5,
          'dti' => 45.0,
          'borrower_state' => 'CA',
          'defaulted' => FALSE,
        ]);

      // Test AI evaluation
      $aiResult = $this->riskManager->evaluate($testLoan);
      
      // Test basic risk score calculation
      $riskScore = $this->riskManager->calculateRiskScore(580, 92.5);

      return [
        '#theme' => 'item_list',
        '#title' => $this->t('Loan Risk Evaluation Test Results'),
        '#items' => [
          $this->t('<strong>Test Loan Data:</strong> FICO: 580, LTV: 92.5%, DTI: 45%'),
          $this->t('<strong>AI Evaluation Result:</strong><br><pre>@result</pre>', ['@result' => $aiResult]),
          $this->t('<strong>Basic Risk Score:</strong> @score/6 (@label)', [
            '@score' => $riskScore['score'],
            '@label' => $riskScore['label']
          ]),
        ],
        '#prefix' => '<div class="openrisk-test-results">',
        '#suffix' => '</div>',
      ];
      
    } catch (\Exception $e) {
      return [
        '#markup' => $this->t('<h3>Loan Risk Evaluation Test - ERROR</h3><div class="messages messages--error">Error: @message</div>', [
          '@message' => $e->getMessage()
        ]),
      ];
    }
  }

}
