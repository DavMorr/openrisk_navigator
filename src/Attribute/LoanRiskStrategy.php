<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a LoanRiskStrategy plugin attribute.
 *
 * Plugin namespace: Plugin\LoanRiskStrategy.
 *
 * @see \Drupal\openrisk_navigator\Plugin\LoanRiskStrategyManager
 * @see \Drupal\openrisk_navigator\Plugin\LoanRiskStrategy\LoanRiskStrategyInterface
 * @see \Drupal\openrisk_navigator\Plugin\LoanRiskStrategy\LoanRiskStrategyPluginBase
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class LoanRiskStrategy extends Plugin {

  /**
   * Constructs a LoanRiskStrategy attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable label of the strategy.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    string $id,
    public readonly string|TranslatableMarkup $label,
    ?string $deriver = NULL,
  ) {
    parent::__construct($id, $deriver);
  }

}
