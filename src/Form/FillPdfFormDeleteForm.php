<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Form\FillPdfFormDeleteForm.
  */

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class FillPdfFormDeleteForm extends ContentEntityConfirmFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('fillpdf.forms_admin');
  }

  public function getConfirmText() {
    return $this->t('Delete');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getEntity()->delete();

    drupal_set_message($this->t('FillPDF form deleted.'));

    $form_state->setRedirect('fillpdf.forms_admin');
  }

}
