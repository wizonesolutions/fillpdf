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

  public function __construct(FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, RequestStack $request_stack) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('fillpdf.link_manipulator'), $container->get('fillpdf.context_manager'), $container->get('request_stack'));
  }

  public function checkLink() {
    $context = $this->linkManipulator->parseLink($this->requestStack->getCurrentRequest());

    // TODO: samples can be viewed by admins
    if ($context['sample']) {
      // ...
    }

    $entities = $this->contextManager->loadEntities($context);

    foreach ($entities as $entity_type => $entity_objects) {
      // If there are any entities in the context that the user can't view,
      // deny access.
      // TODO: return AccessResult:forbiddenIf accordingly
    }

    return AccessResult::allowedIf(TRUE);
  }

}
