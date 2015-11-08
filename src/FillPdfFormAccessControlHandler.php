<?php

/**
 * @file
 * Contains Drupal\fillpdf\FillPdfFormAccessControlHandler.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the FillPDF form entity.
 *
 * @see \Drupal\fillpdf\Entity\FillPdfForm.
 */
class FillPdfFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer pdfs');
        break;
      default:
        return AccessResult::neutral();
        break;
    }
  }

}
