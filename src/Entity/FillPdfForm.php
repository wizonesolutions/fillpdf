<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Entity\FillPdfForm.
 */

namespace Drupal\fillpdf\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\fillpdf\FillPdfFormInterface;

/**
 * Defines the entity for managing uploaded FillPDF forms.
 *
 * @ContentEntityType(
 *   id = "fillpdf_form",
 *   label = @Translation("FillPDF form"),
 *   admin_permission = "administer pdfs",
 *   base_table = "fillpdf_forms",
 *   data_table = "fillpdf_forms_field_data",
 *   entity_keys = {
 *     "id" = "fid",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class FillPdfForm extends ContentEntityBase implements FillPdfFormInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FillPDF Form ID'))
      ->setDescription(t('The ID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE);

    $fields['file'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('The associated managed file.'))
      ->setDescription(t('The associated managed file.'))
      ->setSetting('target_type', 'file');

    // @todo: Revisit this...I would probably need to store the entity and bundle types as well.
//    $fields['default_entity_id'] = BaseFieldDefinition::create('integer')
//      ->setLabel(t('Default entity ID'))
//      ->setDescription(t('The default entity ID to be filled from this FillPDF Form.'));

    $fields['destination_path'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Destination file path for saving'))
      ->setDescription(t('Subfolder in which to save the generated PDF.'));

    $fields['destination_redirect'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Redirect browser to saved PDF'))
      ->setDescription(t("Whether to redirect the browser to the newly-saved PDF or not. By default, it's provided as a download. In some browsers, this will show the PDF in the browser windows; others will still prompt the user to download it."));

    $fields['admin_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Administrative description'))
      ->setDescription(t('A description to help administrators more easily distinguish FillPDF Forms.'));

    return $fields;
  }

}