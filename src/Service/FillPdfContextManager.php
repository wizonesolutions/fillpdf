<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Service\FillPdfContextManager\FillPdfContextManager.
  */

namespace Drupal\fillpdf\Service;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\fillpdf\FillPdfContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfContextManager implements FillPdfContextManagerInterface {

  /** @var EntityManagerInterface $entityManager */
  protected $entityManager;

  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * {@inheritDoc}
   */
  public function loadEntities(array $context) {
    $entities = [];

    foreach ($context['entity_ids'] as $entity_type => $entity_ids) {
      $type_controller = $this->entityManager->getStorage($entity_type);
      $entity_list = $type_controller->loadMultiple($entity_ids);

      if (!empty($entity_list)) {
        // Initialize array.
        $entities += [$entity_type => []];
        $entities[$entity_type] += $entity_list;
      }
    }

    return $entities;
  }

}
