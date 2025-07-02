<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for OpenRisk Navigator AI settings.
 */
final class OpenRiskNavigatorAiSettingsForm extends ConfigFormBase {

  /**
   * The AI provider plugin manager.
   */
  protected AiProviderPluginManager $aiProviderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->aiProviderManager = $container->get('ai.provider');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openrisk_navigator_ai_settings';
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

    $form['ai_integration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AI Integration Settings'),
      '#description' => $this->t('Configure AI provider settings for loan risk assessment.'),
    ];

    // Get available AI providers
    $providers = [];
    foreach ($this->aiProviderManager->getDefinitions() as $plugin_id => $definition) {
      $provider = $this->aiProviderManager->createInstance($plugin_id);
      if ($provider->isUsable('chat')) {
        $providers[$plugin_id] = $definition['label'];
      }
    }

    $form['ai_integration']['ai_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Provider'),
      '#description' => $this->t('Select the AI provider to use for risk assessment.'),
      '#options' => $providers,
      '#default_value' => $config->get('ai_provider') ?? 'openai',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateModelOptions',
        'wrapper' => 'model-options-wrapper',
      ],
    ];

    $form['ai_integration']['ai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('AI Model'),
      '#description' => $this->t('Select the specific model to use for generating risk assessments.'),
      '#options' => $this->getModelOptions($form_state->getValue('ai_provider') ?? $config->get('ai_provider') ?? 'openai'),
      '#default_value' => $config->get('ai_model') ?? 'gpt-4o',
      '#required' => TRUE,
      '#prefix' => '<div id="model-options-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['ai_integration']['enable_ai_fallback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable fallback to plugin strategies'),
      '#description' => $this->t('If enabled, the system will fall back to plugin-based risk strategies when AI is unavailable.'),
      '#default_value' => $config->get('enable_ai_fallback') ?? TRUE,
    ];

    $form['traditional_scoring'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Traditional Risk Strategy Settings'),
    ];

    $form['traditional_scoring']['default_strategy'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Risk Strategy'),
      '#description' => $this->t('The default strategy to use for traditional risk assessment and AI fallback.'),
      '#options' => [
        'conservative_strategy' => $this->t('Conservative Strategy'),
        'moderate_strategy' => $this->t('Moderate Strategy'),
        'aggressive_strategy' => $this->t('Aggressive Strategy'),
      ],
      '#default_value' => $config->get('default_strategy') ?? 'moderate_strategy',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to update model options based on selected provider.
   */
  public function updateModelOptions(array &$form, FormStateInterface $form_state): array {
    $provider_id = $form_state->getValue('ai_provider');
    $form['ai_integration']['ai_model']['#options'] = $this->getModelOptions($provider_id);
    return $form['ai_integration']['ai_model'];
  }

  /**
   * Get available models for a given AI provider.
   */
  protected function getModelOptions(string $provider_id): array {
    try {
      $provider = $this->aiProviderManager->createInstance($provider_id);
      $models = $provider->getConfiguredModels('chat');
      return $models ?: ['gpt-4o' => 'GPT-4o (default)'];
    }
    catch (\Exception $e) {
      return ['gpt-4o' => 'GPT-4o (default)'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('openrisk_navigator.settings')
      ->set('ai_provider', $form_state->getValue('ai_provider'))
      ->set('ai_model', $form_state->getValue('ai_model'))
      ->set('enable_ai_fallback', $form_state->getValue('enable_ai_fallback'))
      ->set('default_strategy', $form_state->getValue('default_strategy'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
