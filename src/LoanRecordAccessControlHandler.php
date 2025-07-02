<?php

namespace Drupal\openrisk_navigator;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for LoanRecord entities.
 */
class LoanRecordAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view loan_record entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit loan_record entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete loan_record entities');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'add loan_record entities');
  }

}
