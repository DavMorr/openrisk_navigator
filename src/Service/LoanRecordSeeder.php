<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Service;

use Drupal\openrisk_navigator\Entity\LoanRecord;

/**
 * Service to generate example LoanRecord content.
 */
class LoanRecordSeeder {

  /**
   * Sample borrower names for seeding.
   */
  private array $borrowerNames = [
    'John Smith', 'Jane Doe', 'Michael Johnson', 'Sarah Williams', 'David Brown',
    'Emily Davis', 'Robert Miller', 'Jessica Wilson', 'Christopher Moore', 'Ashley Taylor',
    'Matthew Anderson', 'Amanda Thomas', 'Joshua Jackson', 'Jennifer White', 'Andrew Harris',
  ];

  /**
   * Sample US states for seeding.
   */
  private array $states = [
    'CA', 'TX', 'NY', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI',
    'NJ', 'VA', 'WA', 'AZ', 'MA', 'TN', 'IN', 'MO', 'MD', 'WI',
  ];

  /**
   * Seeds example LoanRecord entities.
   *
   * @param int $count
   *   The number of LoanRecord entities to create.
   */
  public function seed(int $count = 5): void {
    for ($i = 0; $i < $count; $i++) {
      // Generate realistic loan data.
      $ficoScore = rand(550, 800);
      $loanAmount = rand(150000, 750000);
      // Include decimals.
      $ltvRatio = rand(60, 95) + (rand(0, 99) / 100);
      // Include decimals.
      $dtiRatio = rand(20, 45) + (rand(0, 99) / 100);
      // Higher default rate for low FICO.
      $defaulted = $ficoScore < 600 ? (rand(1, 10) <= 3) : (rand(1, 10) <= 1);

      /** @var \Drupal\openrisk_navigator\Entity\LoanRecord $record */
      $record = LoanRecord::create([
        'loan_id' => 'LOAN-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT) . '-' . date('Y'),
        'borrower_name' => $this->borrowerNames[array_rand($this->borrowerNames)],
        'loan_amount' => $loanAmount,
        'fico_score' => $ficoScore,
        'ltv_ratio' => round($ltvRatio, 2),
        'dti' => round($dtiRatio, 2),
        'borrower_state' => $this->states[array_rand($this->states)],
        'defaulted' => $defaulted,
        'risk_summary' => $this->generateRiskSummary($ficoScore, $ltvRatio, $dtiRatio, $defaulted),
      ]);

      $record->save();
    }
  }

  /**
   * Generates a realistic risk summary based on loan parameters.
   *
   * @param int $ficoScore
   *   The borrower's FICO score.
   * @param float $ltvRatio
   *   The loan-to-value ratio.
   * @param float $dtiRatio
   *   The debt-to-income ratio.
   * @param bool $defaulted
   *   Whether the loan defaulted.
   *
   * @return string
   *   Generated risk summary text.
   */
  private function generateRiskSummary(int $ficoScore, float $ltvRatio, float $dtiRatio, bool $defaulted): string {
    $riskLevel = 'Low';

    if ($ficoScore < 620 || $ltvRatio > 85 || $dtiRatio > 40) {
      $riskLevel = 'High';
    }
    elseif ($ficoScore < 680 || $ltvRatio > 75 || $dtiRatio > 35) {
      $riskLevel = 'Moderate';
    }

    $factors = [];

    if ($ficoScore < 620) {
      $factors[] = 'low credit score';
    }
    elseif ($ficoScore > 750) {
      $factors[] = 'excellent credit score';
    }

    if ($ltvRatio > 85) {
      $factors[] = 'high loan-to-value ratio';
    }
    elseif ($ltvRatio < 70) {
      $factors[] = 'conservative loan-to-value ratio';
    }

    if ($dtiRatio > 40) {
      $factors[] = 'high debt-to-income ratio';
    }
    elseif ($dtiRatio < 30) {
      $factors[] = 'low debt-to-income ratio';
    }

    $summary = "{$riskLevel} risk profile";
    if (!empty($factors)) {
      $summary .= ' due to ' . implode(', ', $factors);
    }

    if ($defaulted) {
      $summary .= '. This loan has defaulted.';
    }
    else {
      $summary .= '. Payment history is current.';
    }

    return $summary . ' Generated on ' . date('Y-m-d H:i:s') . '.';
  }

}
