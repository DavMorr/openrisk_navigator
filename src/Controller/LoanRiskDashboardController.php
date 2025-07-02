<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openrisk_navigator\Service\LoanRiskManager;
use Drupal\openrisk_navigator\Service\LoanRecordSeeder;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns a dashboard page for loan risk overview.
 */
class LoanRiskDashboardController extends ControllerBase {

  /**
   * The loan risk manager.
   *
   * @var \Drupal\openrisk_navigator\Service\LoanRiskManager
   */
  protected LoanRiskManager $riskManager;

  /**
   * The loan record seeder.
   *
   * @var \Drupal\openrisk_navigator\Service\LoanRecordSeeder
   */
  protected LoanRecordSeeder $seeder;

  /**
   * Constructs a LoanRiskDashboardController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\openrisk_navigator\Service\LoanRiskManager $riskManager
   *   The loan risk manager.
   * @param \Drupal\openrisk_navigator\Service\LoanRecordSeeder $seeder
   *   The loan record seeder.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoanRiskManager $riskManager, LoanRecordSeeder $seeder) {
    $this->entityTypeManager = $entityTypeManager;
    $this->riskManager = $riskManager;
    $this->seeder = $seeder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openrisk_navigator.loan_risk_manager'),
      $container->get('openrisk_navigator.loan_record_seeder')
    );
  }

  /**
   * Builds the dashboard page.
   *
   * @return array
   *   Render array for the dashboard.
   */
  public function dashboard(): array {
    $storage = $this->entityTypeManager->getStorage('loan_record');

    // Get total count.
    $total = $storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Get defaulted count.
    $defaulted = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('defaulted', TRUE)
      ->count()
      ->execute();

    // Calculate average FICO and LTV.
    $query = $storage->getQuery()->accessCheck(FALSE);
    $ids = $query->execute();

    // dpm($ids);
    $avgFico = 0;
    $avgLtv = 0;
    $loans = [];

    if (!empty($ids)) {
      $loans = $storage->loadMultiple($ids);
      $ficoSum = 0;
      $ltvSum = 0;
      $count = 0;

      if ($loans) {
        foreach ($loans as $loan) {
          $ficoScore = $loan->get('fico_score')->value;
          $ltvRatio = $loan->get('ltv_ratio')->value;

          if ($ficoScore) {
            $ficoSum += $ficoScore;
            $count++;
          }
          if ($ltvRatio) {
            $ltvSum += $ltvRatio;
          }
        }

        if ($count > 0) {
          $avgFico = round($ficoSum / $count);
          $avgLtv = round($ltvSum / $count, 1);
        }
      }

    }

    $build = [];

    $build['overview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Loan Portfolio Overview'),
      '#attributes' => ['class' => ['loan-overview']],
      'items' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Total Loans: @count', ['@count' => $total]),
          $this->t('Defaulted Loans: @count (@percent%)', [
            '@count' => $defaulted,
            '@percent' => $total > 0 ? round(($defaulted / $total) * 100, 1) : 0,
          ]),
          $this->t('Average FICO Score: @score', ['@score' => $avgFico ?: 'N/A']),
          $this->t('Average LTV Ratio: @ltv%', ['@ltv' => $avgLtv ?: 'N/A']),
        ],
      ],
    ];

    $build['actions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Quick Actions'),
      '#attributes' => ['class' => ['loan-actions']],
      'seed_button' => [
        '#type' => 'link',
        '#title' => $this->t('Seed Test Loan Records'),
        '#url' => Url::fromRoute('openrisk_navigator.seed'),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          'style' => 'margin-bottom: 1em;',
        ],
      ],
      'links' => [
        '#theme' => 'links',
        '#links' => [
          'test' => [
            'title' => $this->t('Risk Evaluation Test'),
            'url' => Url::fromRoute('openrisk_navigator.risk_test'),
          ],
          'settings' => [
            'title' => $this->t('Module Settings'),
            'url' => Url::fromRoute('openrisk_navigator.settings'),
          ],
        ],
        '#attributes' => ['class' => ['action-links']],
      ],
    ];

    $header = [
      'id' => $this->t('ID'),
      'name' => $this->t('Borrower Name'),
      'fico' => $this->t('FICO Score'),
      'ltv' => $this->t('LTV Ratio'),
      'defaulted' => $this->t('Defaulted'),
      'evaluation' => $this->t('Evaluation'),
      'actions' => $this->t('Operations'),
    ];

    $rows = [];

    if ($loans) {

      foreach ($loans as $loan) {
        $strategy = $this->config('openrisk_navigator.settings')->get('default_strategy') ?? 'moderate_strategy';
        $plugin = \Drupal::service('plugin.manager.loan_risk_strategy')->createInstance($strategy);
        $risk = $plugin->evaluate($loan);

        $rows[] = [
          'id' => $loan->id(),
          'name' => $loan->get('borrower_name')->value,
          'fico' => $loan->get('fico_score')->value,
          'ltv' => $loan->get('ltv_ratio')->value,
          'defaulted' => $loan->get('defaulted')->value ? $this->t('Yes') : $this->t('No'),
          'evaluation' => $risk ?: $this->t('N/A'),
          'actions' => [
            'data' => [
              '#type' => 'operations',
              '#links' => [
                'view' => [
                  'title' => $this->t('View'),
                  'url' => $loan->toUrl(),
                ],
                'edit' => [
                  'title' => $this->t('Edit'),
                  'url' => $loan->toUrl('edit-form'),
                ],
              ],
            ],
          ],
        ];
      }

      $build['loan_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No loan records found.'),
      ];

    }

    $build['#cache'] = ['tags' => ['loan_record_list']];

    return $build;
  }

  /**
   * Seeds the database with test loan records.
   */
  public function seed(): RedirectResponse {
    // Use the dedicated seeder service
    $this->seeder->seed(5);
    
    $this->messenger()->addStatus($this->t('5 test loan records have been seeded with realistic data.'));
    return $this->redirect('openrisk_navigator.dashboard');
  }

}
