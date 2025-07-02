<?php

namespace Drupal\openrisk_navigator\Plugin\LoanRiskStrategy;

use Drupal\ai\AiManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @LoanRiskStrategy(
 *   id = "ai_analysis_strategy",
 *   label = @Translation("AI-Based Loan Risk Analysis")
 * )
 */
class AiAnalysisStrategy extends LoanRiskStrategyPluginBase implements ContainerFactoryPluginInterface {

  protected AiManagerInterface $aiManager;

  public function __construct(
    array $configuration, 
    $plugin_id, $plugin_definition, 
    AiManagerInterface $ai_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aiManager = $ai_manager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ai.manager')
    );
  }

  /**
   *
   */
  public function evaluate(array $loanData): string {
    $prompt = [
      'role' => 'system',
      'content' => "You are an expert loan risk analyst. Given a loan's data including FICO score, debt-to-income ratio, 
        loan-to-value ratio, and borrower location, analyze and return a short summary of the loan's risk, followed by 
        a label such as High, Moderate, or Low risk.",
    ];

    $userPrompt = [
      'role' => 'user',
      'content' => 'Analyze the following loan data and return an explanation followed by a risk label:\n\n' . 
        json_encode($loanData, JSON_PRETTY_PRINT),
    ];

    $response = $this->aiManager->chat([$prompt, $userPrompt]);

    return $response->getText();
  }

}
