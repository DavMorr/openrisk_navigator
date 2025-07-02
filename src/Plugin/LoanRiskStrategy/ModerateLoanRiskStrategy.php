<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Provides a moderate risk evaluation strategy.
 */
#[Plugin(
    id: "moderate_strategy"
)]
class ModerateLoanRiskStrategy extends LoanRiskStrategyPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Moderate Risk Strategy';
  }

  /**
   * Evaluate risk with balanced thresholds.
   */
  public function evaluate(LoanRecord $loanData): string {
    $fico = $loanData->get('fico_score')->value ?? 0;
    $ltv = $loanData->get('ltv_ratio')->value ?? 100;
    $dti = $loanData->get('dti')->value ?? 100;

    // Calculate a composite score.
    $score = 0;

    // FICO Score evaluation (0–3 points)
    if ($fico < 580) {
      $score += 3;
    }
    elseif ($fico < 660) {
      $score += 2;
    }
    elseif ($fico < 720) {
      $score += 1;
    }

    // LTV evaluation (0–3 points)
    if ($ltv > 95) {
      $score += 3;
    }
    elseif ($ltv > 85) {
      $score += 2;
    }
    elseif ($ltv > 75) {
      $score += 1;
    }

    // DTI evaluation (0–2 points)
    if ($dti > 50) {
      $score += 2;
    }
    elseif ($dti > 40) {
      $score += 1;
    }

    // Determine risk level based on composite score.
    if ($score >= 6) {
      return 'High Risk';
    }
    elseif ($score >= 3) {
      return 'Moderate Risk';
    }

    return 'Low Risk';
  }

}
