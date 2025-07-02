<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Defines the interface for loan risk strategy plugins.
 */
interface LoanRiskStrategyInterface extends PluginInspectionInterface {

  /**
   * Returns a summary label for the strategy.
   *
   * @return string
   *   The human-readable label for this strategy.
   */
  public function getLabel(): string;

  /**
   * Evaluate risk score based on loan data.
   *
   * @param array $loanData
   *   An associative array containing loan data with the following keys:
   *   - fico_score: (int) FICO credit score (300-850)
   *   - ltv_ratio: (float) Loan-to-value ratio as percentage (0-100+)
   *   - dti: (float|null) Debt-to-income ratio as percentage (optional)
   *   - loan_amount: (float|null) Loan amount in dollars (optional)
   *   - borrower_state: (string|null) Two-letter state code (optional)
   *
   * @return string
   *   Risk evaluation result. Should be one of:
   *   - 'Low Risk'
   *   - 'Moderate Risk'
   *   - 'High Risk'
   */
  public function evaluate(LoanRecord $loanData): string;

}
