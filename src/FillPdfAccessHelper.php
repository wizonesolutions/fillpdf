<?php

/**
 * @file
 * Contains Drupal\fillpdf\FillPdfAccessHelper.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\fillpdf\Service\FillPdfLinkManipulator;
use Drupal\fillpdf\Service\FillPdfContextManager;

/**
 * Class FillPdfAccessHelper.
 *
 * @package Drupal\fillpdf
 */
class FillPdfAccessHelper implements FillPdfAccessHelperInterface {

  /**
   * Drupal\fillpdf\Service\FillPdfLinkManipulator definition.
   *
   * @var \Drupal\fillpdf\Service\FillPdfLinkManipulator
   */
  protected $linkManipulator;

  /**
   * Drupal\fillpdf\Service\FillPdfContextManager definition.
   *
   * @var \Drupal\fillpdf\Service\FillPdfContextManager
   */
  protected $contextManager;

  /**
   * Constructor.
   */
  public function __construct(FillPdfLinkManipulator $link_manipulator, FillPdfContextManager $context_manager) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
  }

  public function canGeneratePdfFromUrlString($url, AccountInterface $account) {
    $context = $this->linkManipulator->parseUrlString($url);
    return $this->canGeneratePdfFromContext($context, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function canGeneratePdfFromLink(Url $link, AccountInterface $account) {
    $context = $this->linkManipulator->parseLink($link);
    return $this->canGeneratePdfFromContext($context, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function canGeneratePdfFromContext(array $context, AccountInterface $account) {
    $is_admin = $account->hasPermission('administer pdfs');
    $can_publish_all = $account->hasPermission('publish all pdfs');
    $cachedAllowed = AccessResult::allowed()
      ->cachePerUser()
      ->cachePerPermissions();
    if ($can_publish_all && $is_admin) {
      return $cachedAllowed;
    }

    $is_sample = $context['sample'];
    if ($is_sample && $is_admin) {
      return $cachedAllowed;
    }

    $cachedForbidden = AccessResult::forbidden()
      ->cachePerUser()
      ->cachePerPermissions();
    $can_publish = $account->hasPermission('publish own pdfs');
    if (!$is_sample && $can_publish) {
      $entities = $this->contextManager->loadEntities($context);

      foreach ($entities as $entity_type => $entity_objects) {
        // If there are any entities in the context that the user can't view,
        // deny access.
        /** @var EntityInterface $entity */
        foreach ($entity_objects as $entity) {
          if (!$entity->access('view', $account)) {
            return $cachedForbidden;
          }
        }
      }

      return $cachedAllowed;
    }

    return $cachedForbidden;
  }

}
