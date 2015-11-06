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

  /** @var \Drupal\fillpdf\FillPdfAccessHelperInterface */
  protected $accessHelper;

  /** @var FillPdfLinkManipulatorInterface */
  protected $linkManipulator;

  /** @var RequestStack $requestStack */
  protected $requestStack;

  /** @var FillPdfContextManagerInterface */
  protected $contextManager;

  /** @var AccountInterface $currentUser */
  protected $currentUser;

  public function __construct(FillPdfAccessHelperInterface $access_helper, FillPdfLinkManipulatorInterface $link_manipulator, FillPdfContextManagerInterface $context_manager, RequestStack $request_stack, AccountInterface $current_user) {
    $this->linkManipulator = $link_manipulator;
    $this->contextManager = $context_manager;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->accessHelper = $access_helper;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('fillpdf.access_helper'), $container->get('fillpdf.link_manipulator'), $container->get('fillpdf.context_manager'), $container->get('request_stack'), $container->get('current_user'));
  }

  public function checkLink() {
    $context = $this->linkManipulator->parseRequest($this->requestStack->getCurrentRequest());
    $account = $this->currentUser;

    return $this->accessHelper->canGeneratePdfFromContext($context, $account);
  }

}
