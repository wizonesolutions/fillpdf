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
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfFormDeleteForm extends ContentEntityConfirmFormBase {

  use StringTranslationTrait;

  /** @var \Drupal\file\FileUsage\FileUsageInterface $fileUsage */
  protected $fileUsage;

  public function __construct(FileUsageInterface $file_usage) {
    $this->fileUsage = $file_usage;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('file.usage'));
  }

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
    /** @var FillPdfFormInterface $fillpdf_form */
    $fillpdf_form = $this->getEntity();

    /** @var FileInterface $file */
    $file = File::load($fillpdf_form->get('file')->first()->target_id);
    $this->fileUsage->delete($file, 'fillpdf', 'fillpdf_form', $fillpdf_form->id());
    $fillpdf_form->delete();

    drupal_set_message($this->t('FillPDF form deleted.'));

    $form_state->setRedirect('fillpdf.forms_admin');
  }

}
