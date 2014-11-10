<?php
/**
  * @file
  * Contains \Drupal\fillpdf\FillPdfAccessController.
  */

namespace Drupal\fillpdf;

use Drupal\Core\Access\AccessResult;

class FillPdfAccessController {

  function checkLink() {
    // TODO: Actually do access checking here
    return AccessResult::allowedIf(TRUE);
  }

}
