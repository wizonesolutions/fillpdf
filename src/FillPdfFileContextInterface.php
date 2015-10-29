<?php

/**
 * @file
 * Contains Drupal\fillpdf\FillPdfFileContextInterface.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining FillPDF file context entities.
 *
 * @ingroup fillpdf
 */
interface FillPdfFileContextInterface extends ContentEntityInterface {

}
