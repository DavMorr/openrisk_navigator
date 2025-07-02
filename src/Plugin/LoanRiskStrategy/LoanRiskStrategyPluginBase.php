<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\Core\Plugin\PluginBase;
use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Base class for Loan Risk Strategy plugins.
 */
abstract class LoanRiskStrategyPluginBase extends PluginBase implements LoanRiskStrategyInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    // Safe fallback.
    return 'Unknown Strategy';
  }

  /**
   * Force implementers to define risk evaluation logic.
   */
  abstract public function evaluate(LoanRecord $loanData): string;

}
