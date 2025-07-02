<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enhanced Loan Record Settings Form - Professional Admin Hub.
 *
 * @ingroup openrisk_navigator
 */
class LoanRecordSettingsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a LoanRecordSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'loan_record_settings';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    // Get quick statistics
    $storage = $this->entityTypeManager->getStorage('loan_record');
    $total_loans = $storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $defaulted_loans = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('defaulted', TRUE)
      ->count()
      ->execute();

    // Admin Hub Header
    $form['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['openrisk-admin-header']],
      'title' => [
        '#markup' => '<h2>' . $this->t('🏦 OpenRisk Navigator Administration') . '</h2>',
      ],
      'subtitle' => [
        '#markup' => '<p class="description">' . $this->t('Comprehensive loan risk management and AI-powered analysis platform') . '</p>',
      ],
    ];

    // Quick Statistics Dashboard
    $form['stats'] = [
      '#type' => 'details',
      '#title' => $this->t('📊 Portfolio Overview'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['openrisk-stats-section']],
    ];

    $default_rate = $total_loans > 0 ? round(($defaulted_loans / $total_loans) * 100, 1) : 0;
    
    $form['stats']['metrics'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['openrisk-metrics-grid']],
      'metric_total' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['metric-item', 'metric-total']],
        '#markup' => '<strong>' . $total_loans . '</strong> Total Loan Records',
      ],
      'metric_defaulted' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['metric-item', 'metric-defaulted']],
        '#markup' => '<strong>' . $defaulted_loans . '</strong> Defaulted Loans (' . $default_rate . '%)',
      ],
      'metric_ai' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['metric-item', 'metric-ai']],
        '#markup' => '<strong>AI Powered</strong> Risk Analysis Active',
      ],
    ];

    // Management Actions Section
    $form['management'] = [
      '#type' => 'details',
      '#title' => $this->t('🎛️ Management Actions'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['openrisk-management-section']],
    ];

    $form['management']['primary_actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['action-buttons-primary']],
    ];

    // Primary Action Buttons
    $primary_actions = [
      [
        'title' => $this->t('📋 View All Loan Records'),
        'url' => Url::fromRoute('entity.loan_record.collection'),
        'description' => $this->t('Browse and manage all loan records in the system'),
        'class' => ['button', 'button--primary', 'button--large'],
      ],
      [
        'title' => $this->t('➕ Add New Loan Record'),
        'url' => Url::fromRoute('entity.loan_record.add_form'),
        'description' => $this->t('Create a new loan record with AI risk analysis'),
        'class' => ['button', 'button--secondary', 'button--large'],
      ],
      [
        'title' => $this->t('📊 Risk Dashboard'),
        'url' => Url::fromRoute('openrisk_navigator.dashboard'),
        'description' => $this->t('View comprehensive risk analytics and portfolio overview'),
        'class' => ['button', 'button--secondary', 'button--large'],
      ],
    ];

    foreach ($primary_actions as $index => $action) {
      $form['management']['primary_actions']['action_' . $index] = [
        '#type' => 'link',
        '#title' => $action['title'],
        '#url' => $action['url'],
        '#attributes' => [
          'class' => $action['class'],
          'title' => $action['description'],
        ],
        '#prefix' => '<div class="action-button-wrapper">',
        '#suffix' => '<div class="action-description">' . $action['description'] . '</div></div>',
      ];
    }

    // Configuration Section
    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('⚙️ System Configuration'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['openrisk-config-section']],
    ];

    $config_actions = [
      [
        'title' => $this->t('🤖 AI Settings'),
        'url' => Url::fromRoute('openrisk_navigator.ai_settings'),
        'description' => $this->t('Configure AI providers, models, and analysis parameters'),
      ],
      [
        'title' => $this->t('🔧 Module Settings'),
        'url' => Url::fromRoute('openrisk_navigator.settings'),
        'description' => $this->t('General module configuration and default settings'),
      ],
      [
        'title' => $this->t('🧪 Risk Evaluation Test'),
        'url' => Url::fromRoute('openrisk_navigator.risk_test'),
        'description' => $this->t('Test AI risk evaluation functionality'),
      ],
      [
        'title' => $this->t('🌱 Seed Test Data'),
        'url' => Url::fromRoute('openrisk_navigator.seed'),
        'description' => $this->t('Generate sample loan records for testing and demonstration'),
      ],
    ];

    $form['configuration']['config_links'] = [
      '#theme' => 'links',
      '#links' => [],
      '#attributes' => ['class' => ['admin-action-links']],
    ];

    foreach ($config_actions as $key => $action) {
      $form['configuration']['config_links']['#links']['config_' . $key] = [
        'title' => $action['title'],
        'url' => $action['url'],
        'attributes' => ['title' => $action['description']],
      ];
    }

    // Field Management Section (Field UI Integration)
    $form['field_management'] = [
      '#type' => 'details',
      '#title' => $this->t('📝 Field & Display Management'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['openrisk-field-section']],
      'description' => [
        '#markup' => '<p>' . $this->t('Configure loan record fields and display settings using the management tools below.') . '</p>',
      ],
    ];

    // Field Management Action Cards
    $field_actions = [
      [
        'title' => $this->t('🔧 Manage Fields'),
        'url' => Url::fromRoute('entity.loan_record.field_ui_fields'),
        'description' => $this->t('Add new configurable fields (base fields defined in code)'),
      ],
      [
        'title' => $this->t('📝 Manage Form Display'),
        'url' => Url::fromRoute('entity.entity_form_display.loan_record.default'),
        'description' => $this->t('Configure how fields appear in add/edit forms'),
      ],
      [
        'title' => $this->t('👁️ Manage Display'),
        'url' => Url::fromRoute('entity.entity_view_display.loan_record.default'),
        'description' => $this->t('Configure how fields appear when viewing records'),
      ],
    ];

    $form['field_management']['field_action_cards'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['action-buttons-secondary']],
    ];

    foreach ($field_actions as $index => $action) {
      $form['field_management']['field_action_cards']['field_action_' . $index] = [
        '#type' => 'link',
        '#title' => $action['title'],
        '#url' => $action['url'],
        '#attributes' => [
          'class' => ['button', 'button--secondary', 'button--medium'],
          'title' => $action['description'],
        ],
        '#prefix' => '<div class="field-action-card">',
        '#suffix' => '<div class="field-action-description">' . $action['description'] . '</div></div>',
      ];
    }

    $form['field_management']['field_note'] = [
      '#markup' => '<div class="messages messages--info">' . 
        $this->t('<strong>Note:</strong> Core fields like borrower_name, fico_score, and risk_summary are defined in code. Use the management tools above to add additional configurable fields.') . 
        '</div>',
    ];

    // API Integration Section
    $form['api_integration'] = [
      '#type' => 'details',
      '#title' => $this->t('🔗 API Integration'),
      '#open' => FALSE,
      '#attributes' => ['class' => ['openrisk-api-section']],
    ];

    $api_endpoints = [
      [
        'title' => $this->t('JSON:API Endpoint'),
        'url' => Url::fromUri('internal:/jsonapi/loan_record/loan_record'),
        'description' => $this->t('RESTful API access to loan records'),
      ],
      [
        'title' => $this->t('JSON:API Documentation'),
        'url' => Url::fromUri('internal:/jsonapi'),
        'description' => $this->t('API documentation and available endpoints'),
      ],
    ];

    foreach ($api_endpoints as $key => $endpoint) {
      $form['api_integration']['endpoint_' . $key] = [
        '#type' => 'link',
        '#title' => $endpoint['title'],
        '#url' => $endpoint['url'],
        '#attributes' => [
          'target' => '_blank',
          'class' => ['button', 'button--small'],
          'title' => $endpoint['description'],
        ],
        '#suffix' => ' <span class="description">(' . $endpoint['description'] . ')</span><br><br>',
      ];
    }

    // Add custom CSS for styling
    $form['#attached']['library'][] = 'openrisk_navigator/admin-interface';

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form is primarily a navigation hub, no settings to save
    $this->messenger()->addMessage($this->t('OpenRisk Navigator administration hub loaded successfully.'));
  }

}
