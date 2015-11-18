<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfFormImportForm.
 */

namespace Drupal\fillpdf\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fillpdf\EntityHelper;
use Drupal\fillpdf\FillPdfFormFieldInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class FillPdfFormImportForm extends EntityForm {

  /** @var \Drupal\serialization\Normalizer\ContentEntityNormalizer */
  protected $serializer;

  /** @var \Drupal\fillpdf\EntityHelper */
  protected $entityHelper;

  public function __construct(SerializerInterface $serializer, EntityHelper $entity_helper) {
    $this->serializer = $serializer;
    $this->entityHelper = $entity_helper;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('serializer'), $container->get('fillpdf.entity_helper'));
  }

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->getEntity();

    $form['paste'] = [
      '#type' => 'details',
      '#title' => t('Paste code'),
      '#open' => TRUE,
    ];
    $form['paste']['code'] = [
      '#type' => 'textarea',
      '#default_value' => '',
      '#rows' => 30,
      '#description' => $this->t('Cut and paste the results of a <em>FillPDF configuration and mappings export</em> here.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['actions']);
    $form['#after_build'] = [];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $code = $form_state->getValue('code');
    $mappings_raw = json_decode($code, TRUE);
    $fillpdf_form = $this->serializer->denormalize($mappings_raw['form'], 'Drupal\fillpdf\Entity\FillPdfForm');

    // Denormalization is a pain; we had to iterate over the fields to actually
    // recompose the $fields array.
    $field_json = $mappings_raw['fields'];
    $fields = [];
    foreach ($field_json as $id => $normalized_field) {
      $field = $this->serializer->denormalize($normalized_field, 'Drupal\fillpdf\Entity\FillPdfFormField');
      $fields[$field->pdf_key->value] = $field;
    }
    if (!is_object($fillpdf_form) || !count($fields)) {
      $form_state->setErrorByName('code', $this->t('There was a problem processing your FillPDF form code. Please do a fresh export from the source and try pasting it again.'));
    }
    else {
      $form_state->setValue('mappings', [
        'form' => $fillpdf_form,
        'fields' => $fields,
      ]);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    /** @var \Drupal\fillpdf\FillPdfFormInterface $fillpdf_form */
    $fillpdf_form = $this->getEntity();

    $mappings = $form_state->getValue('mappings');

    /** @var \Drupal\fillpdf\FillPdfFormInterface $imported_form */
    $imported_form = $mappings['form'];

    /** @var array $imported_fields */
    $imported_fields = $mappings['fields'];

    // Key the existing FillPDF fields on PDF keys.
    $existing_fields = $this->entityHelper->getFormFields($fillpdf_form);
    $existing_fields_by_key = [];
    foreach ($existing_fields as $existing_field) {
      $existing_fields_by_key[$existing_field->pdf_key->value] = $existing_field;
    }

    // Iterate over FillPdfForm fields and copy them, EXCEPT for IDs and references.
    $fillpdf_form_type = $this->entityTypeManager->getDefinition('fillpdf_form');
    $form_fields_to_ignore = array_filter(array_values($fillpdf_form_type->getKeys()));
    $form_fields_to_ignore[] = 'file';
    foreach ($imported_form->getFields() as $name => $data) {
      if (!in_array($name, $form_fields_to_ignore, TRUE)) {
        $fillpdf_form->{$name} = $data;
      }
    }
    $fillpdf_form->save();

    drupal_set_message($this->t('Successfully imported FillPDF form configuration.'));

    // Iterate over each FillPdfFormField and override matching PDF keys
    // (if any current fields have them).
    $fillpdf_field_type = $this->entityTypeManager->getDefinition('fillpdf_form_field');
    $field_fields_to_ignore = array_filter(array_values($fillpdf_field_type->getKeys()));
    $field_fields_to_ignore[] = 'fillpdf_form';

    $existing_field_pdf_keys = array_keys($existing_fields_by_key);
    /**
     * @var string $pdf_key
     * @var FillPdfFormFieldInterface $imported_field
     */
    foreach ($imported_fields as $pdf_key => $imported_field) {
      // If the imported field's PDF key matching the PDF key of the
      // existing field, then copy the constituent entity fields.
      // I know: they're both called fields. It's confusing as hell.
      // I am sorry.
      if (in_array($pdf_key, $existing_field_pdf_keys, TRUE)) {
        /** @var FillPdfFormFieldInterface $existing_field_by_key */
        $existing_field_by_key = $existing_fields_by_key[$pdf_key];
        foreach ($imported_field->getFields() as $imported_field_name => $imported_field_data) {
          if (!in_array($imported_field_name, $field_fields_to_ignore, TRUE)) {
            $existing_field_by_key->{$imported_field_name} = $imported_field_data;
          }
        }
        $existing_field_by_key->save();
      }
      else {
        drupal_set_message($this->t('Your code contained field mappings for the PDF field key <em>@pdf_key</em>, but it does not exist on this form. Therefore, it was ignored.', ['@pdf_key' => $pdf_key]), 'warning');
      }
    }

    drupal_set_message($this->t('Successfully imported matching PDF field keys. If any field mappings failed to import, they are listed above.'));

    $form_state->setRedirect('entity.fillpdf_form.edit_form', ['fillpdf_form' => $fillpdf_form->id()]);
  }

}
