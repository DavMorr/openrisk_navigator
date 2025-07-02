<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Form;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\views\Views;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openrisk_navigator\Plugin\LoanRiskStrategyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Configure settings for the OpenRisk Navigator module.
 */
class LoanRiskSettingsForm extends ConfigFormBase {

  /**
   * The plugin manager for loan risk strategies.
   */
  protected LoanRiskStrategyManager $pluginManager;

  /**
   * Constructs a new LoanRiskSettingsForm object.
   */
  public function __construct(LoanRiskStrategyManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.loan_risk_strategy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'loan_risk_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openrisk_navigator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('openrisk_navigator.settings');
    $plugin_definitions = $this->pluginManager->getDefinitions();

    $plugin_options = [];
    foreach ($plugin_definitions as $plugin_id => $definition) {
      $plugin_options[$plugin_id] = $definition['label'] ?? $plugin_id;
    }

    // Add Dashboard link section.
    $dashboard_url = Url::fromRoute('openrisk_navigator.dashboard');
    $link = Link::fromTextAndUrl($this->t('OpenRisk Dashboard'), $dashboard_url)->toRenderable();
    $link['#attributes'] = ['class' => ['button', 'button--primary', 'dashboard-link']];

    $form['dashboard_link'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dashboard-link-container']],
      'link' => $link,
    ];

    // Add Loan Records Dashboard view page link if enabled and accessible.
    $route_provider = \Drupal::service('router.route_provider');
    $route_name = 'view.openrisk_dashboard.dashboard';
    $view = Views::getView('openrisk_dashboard');

    if ($view) {
      $view->initDisplay();

      if ($view->displayHandlers->has('dashboard')) {
        try {
          // This will throw immediately if the route doesn't exist.
          $route_provider->getRouteByName($route_name);

          $dashboard_url = Url::fromRoute($route_name);
          $link = Link::fromTextAndUrl($this->t('Loan Records Dashboard'), $dashboard_url)->toRenderable();
          $link['#attributes'] = ['class' => ['button', 'button--primary', 'dashboard-link']];

          $form['dashboard_ops_link'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dashboard-link-container']],
            'link' => $link,
          ];
        }
        catch (RouteNotFoundException $e) {
          \Drupal::logger('openrisk_navigator')->notice('Dashboard view route not found; view may be disabled.');
        }
      }
    }

    $form['default_strategy'] = [
      '#type' => 'select',
      '#title' => $this->t('Default loan risk strategy'),
      '#options' => $plugin_options,
      '#default_value' => $config->get('default_strategy') ?? 'conservative_strategy',
      '#description' => $this->t('Choose the strategy plugin to use by default when evaluating loans.'),
    ];

    $form['ai_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI features'),
      '#default_value' => $config->get('ai_enabled') ?? FALSE,
      '#description' => $this->t('Toggle AI-powered risk evaluations (if available).'),
    ];

    $form['ai_loading_style'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Loading Indicator Style'),
      '#options' => [
        'basic' => $this->t('Basic (Simple spinner and text)'),
        'enhanced' => $this->t('Enhanced (Progress bar with status updates)'),
      ],
      '#default_value' => $config->get('ai_loading_style') ?? 'basic',
      '#description' => $this->t('Choose the style of loading indicator shown during AI processing.'),
      '#states' => [
        'visible' => [
          ':input[name="ai_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['fico_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum FICO for low risk'),
      '#default_value' => $config->get('fico_threshold') ?? 670,
    ];

    $form['ltv_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum LTV for low risk'),
      '#default_value' => $config->get('ltv_threshold') ?? 80,
    ];

    $form['risk_thresholds'] = [
      '#type' => 'details',
      '#title' => $this->t('Risk score thresholds'),
      '#open' => TRUE,
    ];

    $form['risk_thresholds']['high_risk_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('High risk threshold score'),
      '#default_value' => $config->get('risk_thresholds.high_risk_threshold') ?? 5,
    ];

    $form['risk_thresholds']['moderate_risk_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Moderate risk threshold score'),
      '#default_value' => $config->get('risk_thresholds.moderate_risk_threshold') ?? 3,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('openrisk_navigator.settings')
      ->set('default_strategy', $form_state->getValue('default_strategy'))
      ->set('ai_enabled', (bool) $form_state->getValue('ai_enabled'))
      ->set('fico_threshold', (int) $form_state->getValue('fico_threshold'))
      ->set('ltv_threshold', (int) $form_state->getValue('ltv_threshold'))
      ->set('risk_thresholds', [
        'high_risk_threshold' => (int) $form_state->getValue(['risk_thresholds', 'high_risk_threshold']),
        'moderate_risk_threshold' => (int) $form_state->getValue(['risk_thresholds', 'moderate_risk_threshold']),
      ])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
