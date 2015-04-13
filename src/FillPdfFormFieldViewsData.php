<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfFormFieldViewsData.
  */

namespace Drupal\fillpdf;

use Drupal\views\EntityViewsData;

class FillPdfFormFieldViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fillpdf_fields']['table']['group'] = $data['fillpdf_fields']['table']['base']['title'] = t('FillPDF form fields');

    $data['fillpdf_fields']['table']['base']['help'] = t('FillPDF form fields represent fields in an uploaded FillPDF PDF.');

    return $data;
  }

}
