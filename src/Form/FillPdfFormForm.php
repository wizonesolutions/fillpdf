<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfFormForm extends ContentEntityForm {

  protected $adminFormHelper;

  public function __construct(FillPdfAdminFormHelperInterface $admin_form_helper) {
    $this->adminFormHelper = $admin_form_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('fillpdf.admin_form_helper'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var FillPdfForm $entity */
    $entity = $this->entity;

    $form['tokens'] = array(
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
      '#weight' => 11,

      'token_tree' => $this->adminFormHelper->getAdminTokenForm(),
    );

    $form['fillpdf_fields'] = FillPdf::embedView('fillpdf_form_fields',
      'block_1',
      $entity->id());

    $form['fillpdf_fields']['#weight'] = 100;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->save();
  }

}
