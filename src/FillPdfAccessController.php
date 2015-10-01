<?php
/**
 * @file
 * Contains \Drupal\fillpdf\FillPdfAccessController.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;

class FillPdfAccessController implements ContainerInjectionInterface {

  /** @var FillPdfLinkManipulatorInterface $linkManipulator */
  protected $linkManipulator;

  /** @var RequestStack $requestStack */
  protected $requestStack;

  /** @var FillPdfContextManagerInterface $contextManager */
  protected $contextManager;

  /** @var AccountInterface $currentUser */
  protected $currentUser;

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, RequestStack $request_stack, AccountInterface $current_user) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('fillpdf.link_manipulator'), $container->get('fillpdf.context_manager'), $container->get('request_stack'), $container->get('current_user'));
  }

  public function checkLink() {
    $context = $this->linkManipulator->parseLink($this->requestStack->getCurrentRequest());

    $is_admin = $this->currentUser->hasPermission('administer pdfs');
    $can_publish_all = $this->currentUser->hasPermission('publish all pdfs');
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
    $can_publish = $this->currentUser->hasPermission('publish own pdfs');
    if (!$is_sample && $can_publish) {
      $entities = $this->contextManager->loadEntities($context);

      foreach ($entities as $entity_type => $entity_objects) {
        // If there are any entities in the context that the user can't view,
        // deny access.
        /** @var EntityInterface $entity */
        foreach ($entity_objects as $entity) {
          if (!$entity->access('view', $this->currentUser)) {
            return $cachedForbidden;
          }
        }
      }

      return $cachedAllowed;
    }

    return $cachedForbidden;
  }

}
