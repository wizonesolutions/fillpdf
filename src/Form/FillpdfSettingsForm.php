<?php

/**
 * @file
 * Contains \Drupal\fillpdf\Form\FillPdfSettingsForm.
 */
namespace Drupal\fillpdf\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\fillpdf\Component\Utility\FillPdf;

class FillPdfSettingsForm extends ConfigFormBase {
  public function getFormId() {
    return 'fillpdf_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fillpdf.settings');
    $fillpdf_service = $config->get('fillpdf_service_backend');

    // Assemble service options. Warning messages will be added next as needed.
    $options = array(
      'pdftk' => $this->t('Use locally-installed pdftk: You will need a VPS or a dedicated server so you can install pdftk: (!see_documentation).', array('!see_documentation' => $this->l($this->t('see documentation'),  Url::fromUri('http://drupal.org/documentation/modules/fillpdf')))),
      'local' => $this->t('Use locally-installed PHP/JavaBridge: You will need a VPS or dedicated server so you can deploy PHP/JavaBridge on Apache Tomcat: (!see_documentation).', array('!see_documentation' => $this->l($this->t('see documentation'),  Url::fromUri('http://drupal.org/documentation/modules/fillpdf')))),
      'fillpdf_service' => $this->t('Use FillPDF Service: Sign up for <a href="https://fillpdf-service.com">FillPDF Service</a>.'),
    );

    // Check for JavaBridge.
    if (!(file_exists(drupal_get_path('module', 'fillpdf') . '/lib/JavaBridge/java/Java.inc'))) {
      $options['local'] .= '<div class="messages warning">' . $this->t('JavaBridge is not installed locally.') . '</div>';
    }

    // Check for pdftk.
    $status = FillPdf::checkPdftkPath(fillpdf_pdftk_path());
    if ($status === FALSE) {
      $options['pdftk'] .= '<div class="messages warning">' . $this->t('pdftk is not properly installed.') . '</div>';
    }

    $form['fillpdf_service_backend'] = array(
      '#type' => 'radios',
      '#title' => $this->t('PDF-filling service'),
      '#description' => $this->t('This module requires the use of one of several external PDF manipulation tools. Choose the service you would like to use.'),
      '#default_value' => !empty($fillpdf_service) ? $fillpdf_service : 'fillpdf_service',
      '#options' => $options,
    );
    $form['fillpdf_service'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Configure FillPDF Service'),
      '#collapsible' => TRUE,
      '#collapsed' => $fillpdf_service !== 'fillpdf_service',
    );
    $form['fillpdf_service']['fillpdf_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('fillpdf_api_key', ''),
      '#description' => $this->t('You need to sign up for an API key at <a href="https://fillpdf-service.com">FillPDF Service</a>'),
    );
    $form['fillpdf_service']['fillpdf_remote_protocol'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Use HTTPS?'),
      '#description' => $this->t('It is recommended to select <em>Use HTTPS</em> for this option. Doing so will help prevent
      sensitive information in your PDFs from being intercepted in transit between your server and the remote service.'),
      '#default_value' => $config->get('fillpdf_remote_protocol'),
      '#options' => array(
        'https' => $this->t('Use HTTPS'),
        'http' => $this->t('Do not use HTTPS'),
      ),
    );
    $form['fillpdf_pdftk_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Configure path to pdftk'),
      '#description' => $this->t("If FillPDF is not detecting your pdftk installation, you can specify the full path to the program here. Include the program name as well. For example, <em>/usr/bin/pdftk</em> is a valid value. You can almost always leave this field blank. If you should set it, you'll probably know."),
      '#default_value' => $config->get('fillpdf_pdftk_path'),
    );

    $form['#attached'] = array('library' => array('fillpdf/fillpdf.admin.settings'));

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('fillpdf_pdftk_path')) {
      $status = FillPdf::checkPdftkPath($form_state->getValue('fillpdf_pdftk_path'));
      if ($status === FALSE) {
        $form_state->setErrorByName('fillpdf_pdftk_path', $this->t('The path you have entered for
      <em>pdftk</em> is invalid. Please enter a valid path.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save form values.
    $this->config('fillpdf.settings')
      ->set('fillpdf_service_backend', $form_state->getValue('fillpdf_service_backend'))
      ->set('fillpdf_api_key', $form_state->getValue('fillpdf_api_key'))
      ->set('fillpdf_remote_protocol', $form_state->getValue('fillpdf_remote_protocol'))
      ->set('fillpdf_pdftk_path', $form_state->getValue('fillpdf_pdftk_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['fillpdf.settings'];
  }
}
