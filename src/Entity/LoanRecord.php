<?php

declare(strict_types=1);

namespace Drupal\openrisk_navigator\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Loan Record entity.
 */
#[ContentEntityType(
  id: "loan_record",
  label: new TranslatableMarkup("Loan Record"),
  label_collection: new TranslatableMarkup("Loan Records"),
  label_singular: new TranslatableMarkup("loan record"),
  label_plural: new TranslatableMarkup("loan records"),
  label_count: [
    "singular" => "@count loan record",
    "plural" => "@count loan records",
  ],
  base_table: "loan_record",
  admin_permission: "administer loan record entities",
  entity_keys: [
    "id" => "id",
    "uuid" => "uuid",
    "label" => "borrower_name",
  ],
  handlers: [
    "view_builder" => "Drupal\Core\Entity\EntityViewBuilder",
    "list_builder" => "Drupal\openrisk_navigator\LoanRecordListBuilder",
    "views_data" => "Drupal\openrisk_navigator\LoanRecordViewsData",
    "form" => [
      "add" => "Drupal\openrisk_navigator\Form\LoanRecordForm",
      "edit" => "Drupal\openrisk_navigator\Form\LoanRecordForm",
      "delete" => "Drupal\Core\Entity\ContentEntityDeleteForm",
    ],
    "access" => "Drupal\openrisk_navigator\LoanRecordAccessControlHandler",
    "route_provider" => [
      "default" => "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
    ],
  ],
  links: [
    "canonical" => "/loan-record/{loan_record}",
    "add-form" => "/loan-record/add",
    "edit-form" => "/loan-record/{loan_record}/edit",
    "delete-form" => "/loan-record/{loan_record}/delete",
    "collection" => "/admin/content/loan-records",
  ],
  field_ui_base_route: "entity.loan_record.settings"
)]
class LoanRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The unique ID of the Loan Record.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Loan Record.'))
      ->setReadOnly(TRUE);

    $fields['loan_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Loan ID'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['borrower_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Borrower Name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
        'settings' => [
          'placeholder' => 'Enter borrower full name',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['loan_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Loan Amount'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -9,
        'settings' => [
          'placeholder' => 'e.g. 350000.00',
          'size' => 15,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -9,
        'settings' => [
          'thousand_separator' => ',',
          'decimal_separator' => '.',
          'scale' => 2,
          'prefix_suffix' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fico_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FICO Score'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -8,
        'settings' => [
          'placeholder' => 'e.g. 720 (300-850 range)',
          'size' => 10,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -8,
        'settings' => [
          'thousand_separator' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ltv_ratio'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('LTV Ratio'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -7,
        'settings' => [
          'placeholder' => 'e.g. 80.00 (as percentage)',
          'size' => 10,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -7,
        'settings' => [
          'decimal_separator' => '.',
          'scale' => 2,
          'suffix' => '%',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dti'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Debt-to-Income Ratio'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
        'settings' => [
          'placeholder' => 'e.g. 35.00 (as percentage)',
          'size' => 10,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -6,
        'settings' => [
          'decimal_separator' => '.',
          'scale' => 2,
          'suffix' => '%',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['borrower_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Borrower State'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
        'settings' => [
          'placeholder' => 'e.g. CA, NY, TX, FL',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['defaulted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Defaulted'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -4,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['risk_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('AI Risk Summary'))
      ->setDescription(t('Auto-generated by AI'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
        'settings' => [
          'placeholder' => 'AI risk summary will be generated automatically when you save this record',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'ai_markdown_formatter',
        'weight' => -3,
        'settings' => [
          'ai_content_indicator' => TRUE,
          'auto_detect_ai_content' => TRUE,
          'enable_security_filtering' => TRUE,
          'fallback_to_plain_text' => TRUE,
          'max_content_length' => 0,
          'trim_summary_length' => 0,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
