<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormExportForm.
 */

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\EntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;

class FillPdfFormExportForm extends EntityForm {

  /** @var \Symfony\Component\Serializer\Serializer */
  protected $serializer;

  /** @var \Drupal\fillpdf\EntityHelper */
  protected $entityHelper;

  public function __construct(Serializer $serializer, EntityHelper $entity_helper) {
    $this->serializer = $serializer;
    $this->entityHelper = $entity_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('serializer'), $container->get('fillpdf.entity_helper'));
  }

  public function form(array $form, FormStateInterface $form_state) {
    parent::form($form, $form_state);

    $entity = $this->getEntity();

    $fields = $this->entityHelper->getFormFields($entity);

    $form_config = array(
      'form' => $this->serializer->normalize($entity),
      'fields' => $this->serializer->normalize($fields),
    );

    $code = $this->serializer->serialize($form_config, 'json');

    $form = array();
    $form['export'] = array(
      '#type' => 'textarea',
      '#title' => t('FillPDF form configuration and mappings'),
      '#default_value' => $code,
      '#rows' => 30,
      '#description' => t('Copy this code and then on the site you want to import to, go to the Edit page for the FillPDF form for which you want to import these mappings, and paste it in there.'),
      '#attributes' => array(
        'style' => 'width: 97%;',
      ),
    );

    return $form;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['actions']);
    $form['#after_build'] = [];
    return $form;
  }

}
