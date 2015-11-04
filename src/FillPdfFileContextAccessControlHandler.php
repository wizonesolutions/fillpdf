<?php

/**
 * @file
 * Contains Drupal\fillpdf\FillPdfFileContextAccessControlHandler.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the FillPDF file context entity.
 *
 * @see \Drupal\fillpdf\Entity\FillPdfFileContext.
 */
class FillPdfFileContextAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // TODO: allow access if user has permission to entities user used to fill in PDF
//        return AccessResult::allowedIfHasPermission($account, 'view fillpdf file context entities');
        return AccessResult::forbidden();

      case 'update':
        return AccessResult::forbidden();

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer pdfs');
    }

    return AccessResult::allowed();
  }

}
