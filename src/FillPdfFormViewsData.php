<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfFormViewsData.
  */

namespace Drupal\fillpdf;

use Drupal\views\EntityViewsData;

class FillPdfFormViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['fillpdf_forms']['table']['group'] = t('FillPDF forms');

    $data['fillpdf_forms']['table']['base']['help'] = t('FillPDF forms are uploaded on the FillPDF administration page and are used by the FillPDF module.');

    return $data;
  }

}
