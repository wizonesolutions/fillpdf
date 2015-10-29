<?php

/**
 * @file
 * Contains Drupal\fillpdf\Entity\FillPdfFileContext.
 */

namespace Drupal\fillpdf\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for FillPDF file context entities.
 */
class FillPdfFileContextViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fillpdf_file_context']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('FillPDF file context'),
      'help' => $this->t('The FillPDF file context ID.'),
    );

    return $data;
  }

}
