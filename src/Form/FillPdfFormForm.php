<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormForm.
 */
namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\FillPdfAdminFormHelperInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FillPdfFormForm extends ContentEntityForm {

  protected $adminFormHelper;
  protected $linkManipulator;

  public function __construct(FillPdfAdminFormHelperInterface $admin_form_helper,
                              FillPdfLinkManipulatorInterface $link_manipulator) {
    $this->adminFormHelper = $admin_form_helper;
    $this->linkManipulator = $link_manipulator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fillpdf.admin_form_helper'),
      $container->get('fillpdf.link_manipulator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var FillPdfFormInterface $entity */
    $entity = $this->entity;

    $form['tokens'] = array(
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
      '#weight' => 11,
      'token_tree' => $this->adminFormHelper->getAdminTokenForm(),
    );

    $entity_types = array();
    $entity_type_definitions = $this->entityManager->getDefinitions();

    foreach ($entity_type_definitions as $machine_name => $definition) {
      $label = $definition->getLabel();
      $entity_types[$machine_name] = "$machine_name ($label)";
    }

    // @todo: Encapsulate this logic into a ::getDefaultEntityType() method on FillPdfForm
    $default_entity_type = $entity->get('default_entity_type')->first()->value;
    if (empty($default_entity_type)) {
      $default_entity_type = 'node';
    }

    $form['default_entity_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default entity type'),
      '#options' => $entity_types,
      '#weight' => 12.5,
      '#default_value' => $default_entity_type,
    );

    $fid = $entity->id();

    /** @var FileInterface $file_entity */
    $file_entity = File::load($entity->get('file')->first()->target_id);
    $pdf_info_weight = 0;
    $form['pdf_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('PDF form information'),
      '#weight' => $form['default_entity_id']['#weight'] + 1,
      'submitted_pdf' => array(
        '#type' => 'item',
        '#title' => t('Uploaded PDF'),
        '#description' => $file_entity->getFileUri(),
        '#weight' => $pdf_info_weight++,
      ),
      // @todo: make work
      'upload_pdf' => array(
        '#type' => 'file',
        '#title' => 'Update PDF template',
        '#description' => 'Update the PDF template used by this form',
        '#weight' => $pdf_info_weight++,
      ),
      'sample_populate' => array(
        '#type' => 'item',
        '#title' => 'Sample PDF',
        '#description' => $this->l($this->t('See which fields are which in this PDF.'),
            $this->linkManipulator->generateLink(array(
              'fid' => $fid,
              'sample' => TRUE,
            ))) . '<br />' .
          $this->t('If you have set a custom path on this PDF, the sample will be saved there silently.'),
        '#weight' => $pdf_info_weight++,
      ),
      'form_id' => array(
        '#type' => 'item',
        '#title' => 'Form Info',
        '#description' => "Form ID: [$fid].  Populate this form with entity IDs, such as /fillpdf?fid=$fid&entity_type=node&entity_id=10<br/>",
        '#weight' => $pdf_info_weight++,
      ),
    );

    if (!empty($entity->get('default_entity_id')->first()->value)) {
      $form['pdf_info']['populate_default'] = array(
        '#type' => 'item',
        '#title' => 'Fill PDF from default node',
        '#description' => $this->l($this->t('Download this PDF filled with data from the default entity (@entity_type:@entity).',
            array(
              '@entity_type',
              $entity->get('default_entity_type')->first()->value,
              '@entity' => $entity->get('default_entity_id')->first()->value
            )
          ),
            $this->linkManipulator->generateLink(array('fid' => $fid))) . '<br />' .
          $this->t('If you have set a custom path on this PDF, the sample will be saved there silently.'),
        '#weight' => $form['pdf_info']['form_id']['#weight'] - 0.1,
      );
    }

    $form['additional_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Additional settings'),
      '#weight' => $form['pdf_info']['#weight'] + 1,
      '#open' => $entity->get('destination_path')
          ->first()->value || $entity->get('destination_redirect')
          ->first()->value,
    );

    $form['destination_path']['#group'] = 'additional_settings';
    $form['scheme']['#group'] = 'additional_settings';
    $form['destination_redirect']['#group'] = 'additional_settings';
    $form['replacements']['#group'] = 'additional_settings';
    $form['replacements']['#weight'] = 1;

    $form['fillpdf_fields']['fields'] = FillPdf::embedView('fillpdf_form_fields',
      'block_1',
      $entity->id());

    $form['fillpdf_fields']['#weight'] = 100;

    // @todo: Add import/export links once those routes actually exist

    return $form;
  }

  public function validate(array $form, FormStateInterface $form_state) {
    // @todo: default_entity_id without a default entity type might not make sense. but maybe defaulting to node is fine for now.

    return parent::validate($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var FillPdfFormInterface $entity */
    $entity = $this->getEntity();

    $entity->set('default_entity_type', $form_state->getValue('default_entity_type'));

    $entity->save();
  }

}
