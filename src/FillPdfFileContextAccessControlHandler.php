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

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit fillpdf file context entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete fillpdf file context entities');
    }

    return AccessResult::allowed();
  }

}
