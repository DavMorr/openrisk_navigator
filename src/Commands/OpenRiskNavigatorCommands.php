<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\openrisk_navigator\Entity\LoanRecord;
use Drupal\openrisk_navigator\Service\LoanRiskManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\AutowireTrait;

/**
 * Drush commands for OpenRisk Navigator.
 */
final class OpenRiskNavigatorCommands extends DrushCommands {
  
  use AutowireTrait;

  /**
   * The loan risk manager service.
   *
   * @var \Drupal\openrisk_navigator\Service\LoanRiskManager
   */
  protected LoanRiskManager $loanRiskManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a OpenRiskNavigatorCommands object.
   *
   * @param \Drupal\openrisk_navigator\Service\LoanRiskManager $loanRiskManager
   *   The loan risk manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(LoanRiskManager $loanRiskManager, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->loanRiskManager = $loanRiskManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('openrisk_navigator.loan_risk_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Seed sample LoanRecord entities.
   *
   * @param int $count
   *   Number of loan records to create.
   */
  #[CLI\Command(name: 'openrisk-navigator:seed', description: 'Seed sample LoanRecord entities.')]
  #[CLI\Argument(name: 'count', description: 'Number of loan records to create.')]
  #[CLI\Usage(name: 'openrisk-navigator:seed 10', description: 'Create 10 sample loan records.')]
  #[CLI\Alias(name: 'orn:seed')]
  public function seed(int $count = 5): void {
    $states = ['CA', 'TX', 'FL', 'NY', 'PA', 'IL', 'OH', 'GA', 'NC', 'MI'];
    
    for ($i = 0; $i < $count; $i++) {
      $loanRecord = LoanRecord::create([
        'loan_id' => 'LOAN-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
        'loan_amount' => rand(100000, 800000),
        'fico_score' => rand(500, 850),
        'ltv_ratio' => rand(60, 95),
        'borrower_state' => $states[array_rand($states)],
        'defaulted' => rand(0, 100) > 90, // 10% chance of default
      ]);
      $loanRecord->save();
    }

    $this->output()->writeln("<info>Successfully seeded $count loan records.</info>");
  }

  /**
   * Analyze risk for all loan records.
   *
   * @param string $strategy
   *   The risk evaluation strategy to use.
   */
  #[CLI\Command(name: 'openrisk-navigator:analyze', description: 'Analyze risk for all loan records.')]
  #[CLI\Argument(name: 'strategy', description: 'Risk evaluation strategy (conservative, moderate, aggressive).')]
  #[CLI\Option(name: 'update', description: 'Update the risk_summary field with results.')]
  #[CLI\Usage(name: 'openrisk-navigator:analyze moderate --update', description: 'Analyze all loans with moderate strategy and update summaries.')]
  #[CLI\Alias(name: 'orn:analyze')]
  public function analyze(string $strategy = 'moderate', array $options = ['update' => false]): void {
    $validStrategies = ['conservative_strategy', 'moderate_strategy', 'aggressive_strategy'];
    $strategyId = $strategy . '_strategy';
    
    if (!in_array($strategyId, $validStrategies)) {
      $this->output()->writeln("<error>Invalid strategy. Use: conservative, moderate, or aggressive.</error>");
      return;
    }
    
    $storage = $this->entityTypeManager->getStorage('loan_record');
    $loanIds = $storage->getQuery()->accessCheck(FALSE)->execute();
    
    if (empty($loanIds)) {
      $this->output()->writeln("<comment>No loan records found. Run 'drush openrisk-navigator:seed' first.</comment>");
      return;
    }
    
    $loans = $storage->loadMultiple($loanIds);
    $results = [
      'Low Risk' => 0,
      'Moderate Risk' => 0,
      'High Risk' => 0,
    ];
    
    foreach ($loans as $loan) {
      $loanData = [
        'fico_score' => $loan->get('fico_score')->value ?? 0,
        'ltv_ratio' => $loan->get('ltv_ratio')->value ?? 0,
      ];
      
      $risk = $this->loanRiskManager->evaluateLoan($loanData, $strategyId);
      $results[$risk]++;
      
      if ($options['update']) {
        $loan->set('risk_summary', "Risk Level: $risk (Strategy: $strategy)");
        $loan->save();
      }
    }
    
    $this->output()->writeln("<info>Risk Analysis Complete (" . ucfirst($strategy) . " Strategy):</info>");
    foreach ($results as $riskLevel => $count) {
      $this->output()->writeln("  $riskLevel: $count loans");
    }
    
    if ($options['update']) {
      $this->output()->writeln("<info>Risk summaries updated.</info>");
    }
  }
  
  /**
   * Clear all loan records.
   */
  #[CLI\Command(name: 'openrisk-navigator:clear', description: 'Delete all loan records.')]
  #[CLI\Option(name: 'confirm', description: 'Skip confirmation prompt.')]
  #[CLI\Alias(name: 'orn:clear')]
  public function clear(array $options = ['confirm' => false]): void {
    if (!$options['confirm']) {
      $confirm = $this->io()->confirm('Are you sure you want to delete all loan records?', false);
      if (!$confirm) {
        $this->output()->writeln("<comment>Operation cancelled.</comment>");
        return;
      }
    }
    
    $storage = $this->entityTypeManager->getStorage('loan_record');
    $loanIds = $storage->getQuery()->accessCheck(FALSE)->execute();
    
    if (empty($loanIds)) {
      $this->output()->writeln("<comment>No loan records to delete.</comment>");
      return;
    }
    
    $entities = $storage->loadMultiple($loanIds);
    $count = count($entities);
    
    $storage->delete($entities);
    $this->output()->writeln("<info>Deleted $count loan records.</info>");
  }

  /**
   * Setup permissions for API access.
   */
  #[CLI\Command(name: 'openrisk-navigator:setup-api', description: 'Setup permissions for anonymous API access.')]
  #[CLI\Option(name: 'enable', description: 'Enable anonymous access to loan records.')]
  #[CLI\Option(name: 'disable', description: 'Disable anonymous access to loan records.')]
  #[CLI\Alias(name: 'orn:api')]
  public function setupApi(array $options = ['enable' => false, 'disable' => false]): void {
    $userStorage = $this->entityTypeManager->getStorage('user_role');
    $anonymousRole = $userStorage->load('anonymous');
    
    if (!$anonymousRole) {
      $this->output()->writeln("<error>Anonymous role not found.</error>");
      return;
    }
    
    $permission = 'view loan_record entities';
    
    if ($options['enable']) {
      if (!$anonymousRole->hasPermission($permission)) {
        $anonymousRole->grantPermission($permission);
        $anonymousRole->save();
        $this->output()->writeln("<info>✅ Granted '$permission' to anonymous users.</info>");
        $this->output()->writeln("<info>🔗 API now accessible at: https://127.0.0.1:55378/jsonapi/loan_record/loan_record</info>");
      } else {
        $this->output()->writeln("<comment>Anonymous users already have '$permission' permission.</comment>");
      }
      return;
    }
    
    if ($options['disable']) {
      if ($anonymousRole->hasPermission($permission)) {
        $anonymousRole->revokePermission($permission);
        $anonymousRole->save();
        $this->output()->writeln("<info>🔒 Revoked '$permission' from anonymous users.</info>");
        $this->output()->writeln("<comment>API now requires authentication.</comment>");
      } else {
        $this->output()->writeln("<comment>Anonymous users don't have '$permission' permission.</comment>");
      }
      return;
    }
    
    // Default: Show current status
    $hasPermission = $anonymousRole->hasPermission($permission);
    $status = $hasPermission ? '✅ ENABLED' : '❌ DISABLED';
    
    $this->output()->writeln("<info>API Access Status: $status</info>");
    $this->output()->writeln("<info>Permission: '$permission'</info>");
    
    if ($hasPermission) {
      $this->output()->writeln("<info>🔗 Anonymous API access available at:</info>");
      $this->output()->writeln("<info>   https://127.0.0.1:55378/jsonapi/loan_record/loan_record</info>");
      $this->output()->writeln("");
      $this->output()->writeln("<comment>To disable: drush orn:api --disable</comment>");
    } else {
      $this->output()->writeln("<comment>API requires authentication.</comment>");
      $this->output()->writeln("");
      $this->output()->writeln("<info>To enable anonymous access: drush orn:api --enable</info>");
    }
  }

}
