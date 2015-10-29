<?php

/**
 * @file
 * Contains Drupal\fillpdf\Entity\FillPdfFileContext.
 */

namespace Drupal\fillpdf\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\fillpdf\FillPdfFileContextInterface;
use Drupal\user\UserInterface;

/**
 * Defines the FillPDF file context entity.
 *
 * @ingroup fillpdf
 *
 * @ContentEntityType(
 *   id = "fillpdf_file_context",
 *   label = @Translation("FillPDF file context"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\Entity\FillPdfFileContextViewsData",
 *
 *     "access" = "Drupal\fillpdf\FillPdfFileContextAccessControlHandler",
 *   },
 *   base_table = "fillpdf_file_context",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class FillPdfFileContext extends ContentEntityBase implements FillPdfFileContextInterface {
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the FillPDF file context entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPDF file context entity.'))
      ->setReadOnly(TRUE);

    $fields['context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Generation context'))
      ->setDescription(t('The normalized FillPDF Link (URL) that was used to generate the PDF.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
