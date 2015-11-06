<?php

/**
 * @file
 * Contains Drupal\fillpdf\FillPdfAccessHelperInterface.
 */

namespace Drupal\fillpdf;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Interface FillPdfAccessHelperInterface.
 *
 * @package Drupal\fillpdf
 */
interface FillPdfAccessHelperInterface {

  /**
   * Provides a way to pass in a FillPDF Link string to check access. Should
   * ultimately pass control to self::canGeneratePdfFromContext().
   *
   * @param string $url
   * The root-relative FillPDF URL that would be used to generate the PDF.
   * e.g. /fillpdf?fid=1&entity_type=node&entity_id=1
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @see canGeneratePdfFromContext()
   *
   * @return AccessResultInterface
   */
  public function canGeneratePdfFromUrlString($url, AccountInterface $account);

  /**
   * Provides a way to check access from a link argument. This function should
   * build a FillPdfLinkManipulator-compatible $context and then pass control
   * to self::canGeneratePdfFromLink().
   *
   * @param \Drupal\Core\Url $link
   * The FillPDF Link containing the entities whose access to check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * The user whose access is being checked.
   *
   * @see canGeneratePdfFromContext()
   *
   * @return AccessResultInterface
   */
  public function canGeneratePdfFromLink(Url $link, AccountInterface $account);

  /**
   * This is the main access checking function of this class.
   * self::canGeneratePdfFromLinkUrl() should delegate to this one.
   *
   * @param array $context
   * As returned by FillPdfLinkManipulator's parse functions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * The user whose access is being checked.
   *
   * @see canGeneratePdfFromLink()
   *
   * @return AccessResultInterface
   */
  public function canGeneratePdfFromContext(array $context, AccountInterface $account);

}
