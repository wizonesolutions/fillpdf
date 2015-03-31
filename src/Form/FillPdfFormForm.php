<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class FillPdfFormForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var FillPdfForm $entity */
    $entity = $this->entity;

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
