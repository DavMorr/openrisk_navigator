<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Service;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\openrisk_navigator\Entity\LoanRecord;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\Exception\AiResponseErrorException;

/**
 * Service to encapsulate logic for scoring and analyzing loan risk.
 */
class LoanRiskManager {

  /**
   * The strategy plugin manager.
   */
  protected PluginManagerInterface $strategyManager;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The AI provider plugin manager.
   */
  protected AiProviderPluginManager $aiProviderManager;

  /**
   * The logger channel.
   */
  protected LoggerChannelInterface $logger;

  /**
   * Constructs a LoanRiskManager object.
   */
  public function __construct(
    PluginManagerInterface $strategyManager,
    ConfigFactoryInterface $configFactory,
    AiProviderPluginManager $aiProviderManager,
    LoggerChannelInterface $logger,
  ) {
    $this->strategyManager = $strategyManager;
    $this->configFactory = $configFactory;
    $this->aiProviderManager = $aiProviderManager;
    $this->logger = $logger;
  }

  /**
   * Evaluates the risk of a loan using AI analysis.
   *
   * @param \Drupal\openrisk_navigator\Entity\LoanRecord $loan
   *   The loan record entity to evaluate.
   *
   * @return string
   *   The AI-generated risk evaluation result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function evaluate(LoanRecord $loan): string {
    // First, try AI evaluation
    try {
      return $this->evaluateWithAI($loan);
    }
    catch (\Throwable $e) {
      $this->logger->warning('AI evaluation failed for loan @id: @message', [
        '@id' => $loan->id() ?: 'unknown',
        '@message' => $e->getMessage(),
      ]);
      
      // Fallback to plugin-based evaluation
      return $this->fallbackEvaluation($loan);
    }
  }

  /**
   * @todo artifact code, but don't remove until confirmed non-breaking.
   */
  public function evaluateLoan(LoanRecord $loan): string {
    $summary = $this->evaluate($loan);
    $this->logger->info('Risk evaluated for loan @id: @summary', [
      '@id' => $loan->id() ?: 'unknown',
      '@summary' => substr($summary, 0, 100) . '...',
    ]);
    return $summary;
  }

  /**
   * Evaluates loan risk using AI provider.
   */
  protected function evaluateWithAI(LoanRecord $loan): string {
    $config = $this->configFactory->get('openrisk_navigator.settings');
    
    // Get AI provider settings
    $providerId = $config->get('ai_provider') ?? 'openai';
    $modelId = $config->get('ai_model') ?? 'gpt-4o';
    
    // Get the AI provider
    $aiProvider = $this->aiProviderManager->createInstance($providerId);
    
    if (!$aiProvider->isUsable('chat')) {
      throw new AiResponseErrorException('AI provider is not usable for chat operations.');
    }

    // Build the prompt
    $prompt = $this->buildLoanRiskPrompt($loan);
    
    // Create chat input
    $chatMessage = new ChatMessage('user', $prompt);
    $chatInput = new ChatInput([$chatMessage]);
    
    // Send request to AI provider
    $response = $aiProvider->chat($chatInput, $modelId);
    
    return $response->getNormalized()->getText();
  }

  /**
   * Builds the AI prompt for loan risk assessment.
   */
  protected function buildLoanRiskPrompt(LoanRecord $loan): string {
    $loanId = $loan->get('loan_id')->value ?? 'Unknown';
    $fico = $loan->get('fico_score')->value;
    $ltv = $loan->get('ltv_ratio')->value;
    $dti = $loan->get('dti')->value;
    $defaulted = $loan->get('defaulted')->value ? 'Yes' : 'No';
    $borrowerName = $loan->get('borrower_name')->value ?? 'Unknown';
    $loanAmount = $loan->get('loan_amount')->value;
    $borrowerState = $loan->get('borrower_state')->value ?? 'Unknown';

    // Safely format loan amount as currency - handle both string and numeric values
    $numericAmount = is_numeric($loanAmount) ? (float) $loanAmount : 0;
    $formattedAmount = $numericAmount > 0 ? '$' . number_format($numericAmount, 2) : 'Unknown';

    return <<<EOT
You are a financial analyst AI trained to assess risk in residential loan applications.

Evaluate the following loan and provide:
1. A one-line **risk label** (e.g., "Low Risk", "Moderate Risk", "High Risk", "Very High Risk").
2. A detailed but concise **justification** (2-3 sentences) using the provided metrics.
3. Key risk factors and recommendations.

**Loan Details:**
- Loan ID: {$loanId}
- Borrower: {$borrowerName}
- Loan Amount: {$formattedAmount}
- FICO Score: {$fico}
- Loan-to-Value (LTV) Ratio: {$ltv}%
- Debt-to-Income (DTI) Ratio: {$dti}%
- Borrower State: {$borrowerState}
- Has Defaulted: {$defaulted}

**Assessment Guidelines:**
- FICO < 620: High risk factor
- LTV > 80%: Increased risk
- DTI > 43%: High risk factor
- Consider geographic and economic factors

Provide a professional, actionable risk assessment.
EOT;
  }

  /**
   * Fallback evaluation using plugin strategies.
   */
  protected function fallbackEvaluation(LoanRecord $loan): string {
    // Load the default strategy from configuration
    $config = $this->configFactory->get('openrisk_navigator.settings');
    $strategyId = $config->get('default_strategy') ?: 'moderate_strategy';

    $plugin = $this->strategyManager->createInstance($strategyId);
    $pluginResult = $plugin->evaluate($loan);  // Pass entity, not array
    
    // Generate a structured fallback summary
    $ficoScore = $loan->get('fico_score')->value ? (int) $loan->get('fico_score')->value : null;
    $ltvRatio = $loan->get('ltv_ratio')->value ? (float) $loan->get('ltv_ratio')->value : null;
    $riskScore = $this->calculateRiskScore($ficoScore, $ltvRatio);
    
    return sprintf(
      "Risk Level: %s\n\nAI evaluation temporarily unavailable. Assessment based on traditional scoring:\n%s\n\nRisk Score: %d/6",
      $riskScore['label'],
      $pluginResult,
      $riskScore['score']
    );
  }

  /**
   * Returns a basic risk score based on FICO and LTV.
   */
  public function calculateRiskScore(?int $fico, ?float $ltv): array {
    if ($fico === NULL || $ltv === NULL) {
      return ['score' => NULL, 'label' => 'Unknown'];
    }

    $score = 0;

    // FICO scoring
    if ($fico < 580) {
      $score += 3;
    }
    elseif ($fico < 660) {
      $score += 2;
    }
    elseif ($fico < 720) {
      $score += 1;
    }

    // LTV scoring
    if ($ltv > 90) {
      $score += 3;
    }
    elseif ($ltv > 80) {
      $score += 2;
    }
    elseif ($ltv > 70) {
      $score += 1;
    }

    $label = match (TRUE) {
      $score >= 5 => 'High Risk',
      $score >= 3 => 'Moderate Risk', 
      $score >= 1 => 'Low Risk',
      default => 'Minimal Risk',
    };

    return ['score' => $score, 'label' => $label];
  }

}
