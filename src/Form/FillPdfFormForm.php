<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;

class FillPdfFormForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var FillPdfForm $entity */
    $entity = $this->entity;

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
