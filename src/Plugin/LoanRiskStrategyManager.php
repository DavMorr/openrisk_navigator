<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\openrisk_navigator\Plugin\LoanRiskStrategy\LoanRiskStrategyInterface;
use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * Manages discovery and instantiation of LoanRiskStrategy plugins.
 *
 * This plugin manager discovers all LoanRiskStrategy plugins using
 * PHP attributes and makes them available for evaluating loan risk
 * using different strategies.
 *
 * @see \Drupal\openrisk_navigator\Plugin\LoanRiskStrategy\LoanRiskStrategyInterface
 * @see \Drupal\openrisk_navigator\Plugin\LoanRiskStrategy\LoanRiskStrategyPluginBase
 */
class LoanRiskStrategyManager extends DefaultPluginManager {

  /**
   * Constructs a new LoanRiskStrategyManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/LoanRiskStrategy',
      $namespaces,
      $module_handler,
      LoanRiskStrategyInterface::class,
      Plugin::class
    );

    $this->alterInfo('loan_risk_strategy_info');
    $this->setCacheBackend($cache_backend, 'loan_risk_strategy_plugins');
  }

}
