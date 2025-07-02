<?php

namespace Drupal\openrisk_navigator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a list builder for LoanRecord entities.
 */
class LoanRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'id' => $this->t('ID'),
      'label' => $this->t('Loan ID'),
    ] + parent::buildHeader();
  }

  /**
   * Builds a row for a loan record entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array representing the row.
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\openrisk_navigator\Entity\LoanRecord $entity */
    return [
      'id' => $entity->id(),
      // 'label' => $entity->toLink(),
      // 'label' => $entity->label() ?: 'Unnamed',
      'label' => $entity->label()
        ? $entity->toLink()
        : Link::fromTextAndUrl($this->t('[No label]'), $entity->toUrl()),
    ] + parent::buildRow($entity);
  }

}
