<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Simple controller to prevent conflicts.
 */
class CustomEntitiesController extends ControllerBase {

  /**
   * Simple overview method.
   */
  public function overview() {
    return [
      '#markup' => '<h1>' . $this->t('Custom Entity Types') . '</h1>' .
        '<p>' . $this->t('This page has been simplified. Please use:') . '</p>' .
        '<ul>' .
        '<li><a href="/admin/structure/loan-record-settings">' . $this->t('Loan Record Settings') . '</a></li>' .
        '<li><a href="/admin/structure/loan-record-settings/display">' . $this->t('Configure AI Markdown Formatter') . '</a></li>' .
        '</ul>',
    ];
  }

}
