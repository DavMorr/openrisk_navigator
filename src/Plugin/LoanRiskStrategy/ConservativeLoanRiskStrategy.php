<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Provides a conservative risk evaluation strategy.
 */
#[Plugin(
  id: "conservative_strategy"
)]
class ConservativeLoanRiskStrategy extends LoanRiskStrategyPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Conservative Risk Strategy';
  }

  /**
   * Evaluates the risk level of a loan using a conservative strategy.
   *
   * @param array $loanData
   *   An associative array containing loan data, including 'fico_score' and 'ltv_ratio'.
   *
   * @return string
   *   The evaluated risk level: 'Low Risk', 'Moderate Risk', or 'High Risk'.
   */
  public function evaluate(LoanRecord $loanData): string {
    $fico = $loanData->get('fico_score')->value ?? 0;
    $ltv = $loanData->get('ltv_ratio')->value ?? 0;
    $dti = $loanData->get('dti')->value ?? 0;

    $score = 0;

    if ($fico < 620) {
      $score += 3;
    }
    elseif ($fico < 660) {
      $score += 2;
    }
    elseif ($fico < 700) {
      $score += 1;
    }

    if ($ltv > 90) {
      $score += 3;
    }
    elseif ($ltv > 80) {
      $score += 2;
    }
    elseif ($ltv > 70) {
      $score += 1;
    }

    if ($dti > 50) {
      $score += 2;
    }
    elseif ($dti > 43) {
      $score += 1;
    }

    if ($score >= 5) {
      return 'High Risk';
    }
    elseif ($score >= 3) {
      return 'Moderate Risk';
    }

    return 'Low Risk';
  }

}
