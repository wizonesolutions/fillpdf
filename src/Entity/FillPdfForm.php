<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Entity\FillPdfForm.
 */

namespace Drupal\fillpdf\Entity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\fillpdf\FillPdfFormInterface;

/**
 * Defines the entity for managing uploaded FillPDF forms.
 *
 * @ContentEntityType(
 *   id = "fillpdf_form",
 *   label = @Translation("FillPDF form"),
 *   handlers = {
 *     "views_data" = "Drupal\fillpdf\FillPdfFormViewsData",
 *     "form" = {
 *       "edit" = "Drupal\fillpdf\Form\FillPdfFormForm",
 *       "delete" = "Drupal\fillpdf\Form\FillPdfFormDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer pdfs",
 *   base_table = "fillpdf_forms",
 *   entity_keys = {
 *     "id" = "fid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/fillpdf/{fillpdf_form}/delete"
 *   }
 * )
 */
class FillPdfForm extends ContentEntityBase implements FillPdfFormInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = array();

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FillPDF Form ID'))
      ->setDescription(t('The ID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the FillPdfForm entity.'))
      ->setReadOnly(TRUE);

    $fields['file'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('The associated managed file.'))
      ->setDescription(t('The associated managed file.'))
      ->setSetting('target_type', 'file');

    // @todo: Figure out how to do this the right way...I get a router rebuild error if I use $url_generator->generateFromRoute()
    $overview_url = Url::fromUri('base://admin/structure/fillpdf')->toString();
    // @todo: what is wrong with the below?
    $fields['admin_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Administrative description'))
      ->setDescription(t('Enter the name of the form here, and it will be shown on the <a href="@overview_url">form overview page</a>. It has no effect on functionality, but it can help you identify which form configuration you want to edit.', array('@overview_url' => $overview_url)))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 0,
      ));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename pattern'))
      ->setDescription(t('Enter a title for this mapping configuration. This will be used for deciding the filename of your PDF. <strong>This field supports tokens.</strong>'))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 10,
        ));

    // @todo: Validate this with a custom constraint or whatever
    $fields['default_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default entity type'))
      ->setDescription(t('The type of the below entity ID.'));

    // @todo: Validate this with a custom constraint, if possible
    $fields['default_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Default entity ID'))
      ->setDescription(t('The default entity ID to be filled from this FillPDF Form.'))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 15,
      ));

    // @todo: set display options on this
    $fields['destination_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Where to save generated PDFs'))
      ->setDescription(t("<p>By default, filled PDFs are not saved to disk; they are simply sent
      directly to the browser for download. Enter a path here to change this behavior (tokens allowed).
      <strong>Warning! Unless you include the &download=1 flag in the FillPDF URL, PDFs will only
      be saved to disk <em>and won't</em> be sent to the browser as well.</strong></p><p>The path
      you specify must be in the following format:<br />
        <ul>
          <li><code>path/to/directory</code> (path will be treated as relative to
          your <em>fillpdf</em> files subdirectory)</li>
          filesystem)</li>
        </ul>
      Note that you are responsible for ensuring that the user under which PHP is running can write to this path. Do not include a trailing slash.</p>"))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 20,
      ]);

    // @todo: add post_save_redirect field for where to send the browser by default after they generate a PDF

    $fields['scheme'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Storage system for generated PDFs')
      ->setDescription(t('This setting is used as the storage/download method for generated PDFs. The use of public files is more efficient, but does not provide any access control. Changing this setting will require you to migrate associated files and data yourself and is not recommended after you have uploaded a template.'))
      ->setSettings([
        'allowed_values' => FillPdfAdminFormHelper::schemeOptions(),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 25,
        'settings' => [
          'options' => FillPdfAdminFormHelper::schemeOptions(),
        ],
      ]);

    $fields['destination_redirect'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Redirect browser directly to saved PDF'))
      ->setDescription(t("<strong>This setting is applicable only if <em>Where to save generated PDFs</em> is set.</strong> Instead of redirecting your visitors to the front page, it will redirect them directly to the PDF. However, if you pass Drupal's <em>destination</em> query string parameter, that will override this setting."))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 30,
        'settings' => array(
          'display_label' => TRUE,
        ),
      ));

    return $fields;
  }

}
