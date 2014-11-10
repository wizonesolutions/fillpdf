<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Controller\HandlePdfController.
  */

namespace Drupal\fillpdf\Controller;

use Drupal\Core\Controller\ControllerBase;

class HandlePdfController extends ControllerBase {

  public function populatePdf() {
    // TODO: Extract the PDF parameters, and act accordingly - this is pretty much just calling FillPdfBackendPluginInterface::populateWithFieldData and then reading the FillPdfForm options to determine whether to serve as a file download or fill in the field data.
  }

}
