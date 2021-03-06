<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Entity\FillPdfFormField.
 */

namespace Drupal\fillpdf\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\fillpdf\FillPdfFormFieldInterface;
use Drupal\fillpdf\Service\FillPdfAdminFormHelper;

/**
 * Defines the entity for managing PDF fields associated with uploaded FillPDF
 * forms.
 *
 * Uses the same access handler as fillpdf_form.
 *
 * @ContentEntityType(
 *   id = "fillpdf_form_field",
 *   label = @Translation("FillPDF form field"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\FillPdfFormFieldViewsData",
 *     "form" = {
 *       "edit" = "Drupal\fillpdf\Form\FillPdfFormFieldForm",
 *     },
 *     "access": "Drupal\fillpdf\FillPdfFormAccessControlHandler",
 *   },
 *   admin_permission = "administer pdfs",
 *   base_table = "fillpdf_fields",
 *   entity_keys = {
 *     "id" = "ffid",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class FillPdfFormField extends ContentEntityBase implements FillPdfFormFieldInterface {

  /**
   * @inheritdoc
   * @todo Fix field descriptions to match D7 version.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['ffid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FillPDF Form Field ID'))
      ->setDescription(t('The ID of the FillPdfFormField entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['fillpdf_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('FillPDF Form ID'))
      ->setDescription(t('The ID of the FillPdfFormField entity.'))
      ->setSetting('target_type', 'fillpdf_form');

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPdfFormField entity.'))
      ->setReadOnly(TRUE);

    $fields['pdf_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PDF Key'))
      ->setDescription(t('The name of the field in the PDF form.'));

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PDF field label'))
      ->setDescription(t('An optional label to help you identify the field.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
      ]);

    $fields['prefix'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text before field value'))
      ->setDescription(t('Text to add to the front of the field value unless the field value is blank.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
      ]);

    $fields['value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Fill pattern'))
      ->setDescription(t('Text and tokens with which to fill in the PDF.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
      ]);

    $fields['suffix'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text after field value'))
      ->setDescription(t('Text to add to the end of the field value unless the field value is blank.'))
      ->setDisplayOptions('form', [
        'type' => 'string_long',
      ]);

    $fields['replacements'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Change text before sending to PDF (Transform values)'))
      ->setDescription(FillPdfAdminFormHelper::getReplacementsDescription())
      ->setDisplayOptions('form', [
        'type' => 'string_long',
      ]);

    return $fields;
  }

}
