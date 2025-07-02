<?php

namespace Drupal\openrisk_navigator;

use Drupal\views\EntityViewsData;

/**
 * Provides Views integration for LoanRecord entities.
 */
class LoanRecordViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Example: Add a field override or custom join logic if needed here.
    return $data;
  }

}
