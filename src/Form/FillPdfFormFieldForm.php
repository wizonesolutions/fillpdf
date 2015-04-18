<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Form\FillPdfFormFieldForm.
  */

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfFormFieldForm extends ContentEntityForm {

  protected $adminFormHelper;

  public function __construct(FillPdfAdminFormHelperInterface $admin_form_helper) {
    $this->adminFormHelper = $admin_form_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.admin_form_helper')
    );
  }

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['token_help'] = $this->adminFormHelper->getAdminTokenForm();

    return $form;
  }

  public function save(array $form, FormStateInterface $form_state) {
    /** @var FillPdfFormInterface $entity */
    $entity = $this->entity;

    $form_state->setRedirect('entity.fillpdf_form.edit_form', [
      'fillpdf_form' => $this->entity->fillpdf_form->target_id,
    ]);

    return parent::save($form, $form_state);
  }

}
