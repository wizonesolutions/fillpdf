<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Form\FillPdfFormFieldForm.
  */

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\FillPdfFormInterface;

class FillPdfFormFieldForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // PDF field key

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
