<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Provides an aggressive loan risk strategy plugin.
 */
#[Plugin(
    id: "aggressive_strategy"
)]
class AggressiveLoanRiskStrategy extends LoanRiskStrategyPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Aggressive Risk Strategy';
  }

  /**
   * Evaluate risk with aggressive thresholds.
   */
  public function evaluate(LoanRecord $loanData): string {
    $fico = $loanData->get('fico_score')->value ?? 0;
    $ltv = $loanData->get('ltv_ratio')->value ?? 0;
    $dti = $loanData->get('dti')->value ?? 0;

    $score = 0;

    if ($fico < 640) {
      $score += 2;
    }

    if ($ltv > 85) {
      $score += 2;
    }

    if ($dti > 45) {
      $score += 2;
    }

    if ($score >= 4) {
      return 'High Risk';
    }
    elseif ($score >= 2) {
      return 'Moderate Risk';
    }

    return 'Low Risk';
  }

}
