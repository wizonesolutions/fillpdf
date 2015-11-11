<?php

/**
 * @file
 * Contains \Drupal\fillpdf\OutputHandler.
 */

namespace Drupal\fillpdf;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Entity\FillPdfFileContext;
use Drupal\fillpdf\Entity\FillPdfForm;
use Psr\Log\LoggerInterface;


/**
 * Class OutputHandler.
 *
 * @package Drupal\fillpdf
 */
class OutputHandler implements OutputHandlerInterface {

  use StringTranslationTrait;

  /** @var Token $token */
  protected $token;

  /** @var \Psr\Log\LoggerInterface $logger */
  protected $logger;

  /** @var \Drupal\fillpdf\FillPdfLinkManipulatorInterface $link_manipulator */
  protected $linkManipulator;

  /**
   * OutputHandler constructor.
   * @param \Drupal\Core\Utility\Token $token
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\fillpdf\FillPdfLinkManipulatorInterface $link_manipulator
   */
  public function __construct(Token $token, LoggerInterface $logger, FillPdfLinkManipulatorInterface $link_manipulator) {
    $this->token = $token;
    $this->logger = $logger;
    $this->linkManipulator = $link_manipulator;
  }

  /**
   * {@inheritdoc}
   */
  public function savePdfToFile(array $context, $destination_path_override = NULL) {
    /** @var FillPdfForm $fillpdf_form */
    $fillpdf_form = $context['form'];

    /** @var array $token_objects */
    $token_objects = $context['token_objects'];

    $destination_path = 'fillpdf';
    if (!empty($fillpdf_form->destination_path->value)) {
      $destination_path = "fillpdf/{$fillpdf_form->destination_path->value}";
    }
    if (!empty($destination_path_override)) {
      $destination_path = "fillpdf/{$destination_path_override}";
    }

    $resolved_destination_path = $this->processDestinationPath($destination_path, $token_objects, $fillpdf_form->scheme->value);
    $path_exists = file_prepare_directory($resolved_destination_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $saved_file = FALSE;
    if ($path_exists === FALSE) {
      $this->logger->critical($this->t("The path %destination_path does not exist and could not be
      automatically created. Therefore, the previous submission was not saved. If
      the URL contained download=1, then the PDF was still sent to the user's browser.
      If you were redirecting them to the PDF, they were sent to the homepage instead.
      If the destination path looks wrong and you have used tokens, check that you have
      used the correct token and that it is available to FillPDF at the time of PDF
      generation.",
        ['%destination_path' => $resolved_destination_path]));
    }
    else {
      // Full steam ahead!
      $saved_file = file_save_data($context['data'], "{$resolved_destination_path}/{$context['filename']}", FILE_EXISTS_RENAME);
      $this->rememberFileContext($saved_file, $context['context']);
    }

    return $saved_file;
  }

  /**
   * @param string $destination_path
   * @param array $token_objects
   * @param string $scheme
   * @return string
   */
  protected function processDestinationPath($destination_path, $token_objects, $scheme = 'public') {
    $orig_path = $destination_path;
    $destination_path = trim($orig_path);
    // Replace any applicable tokens
    $types = [];
    if (isset($token_objects['node'])) {
      $types[] = 'node';
    }
    elseif (isset($token_objects['webform'])) {
      $types[] = 'webform';
    }
    // TODO: Do this kind of replacement with a common service instead, because I'm doing the same thing in like 3 places now.
    foreach ($types as $type) {
      $destination_path = $this->token->replace($destination_path, [$type => $token_objects[$type]], ['clear' => TRUE]);
    }

    // Slap on the files directory in front and return it
    $destination_path = FillPdf::buildFileUri($scheme, $destination_path);
    return $destination_path;
  }

  /**
   * @param \Drupal\file\FileInterface $fillpdf_file
   * @param array $context
   *   An array representing the entities that were used to generate this file.
   *   This array should match the format returned by
   *   FillPdfLinkManipulator::parseLink().
   * @see FillPdfLinkManipulatorInterface::parseLink()
   * @see FileFieldItemList::postSave()
   */
  protected function rememberFileContext(FileInterface $fillpdf_file, array $context) {
    $fillpdf_link = $this->linkManipulator->generateLink($context);

    $fillpdf_file_context = FillPdfFileContext::create([
      'file' => $fillpdf_file,
      'context' => $fillpdf_link->toUriString(),
    ]);

    // The file field will automatically add file usage information upon save.
    $fillpdf_file_context->save();
  }

}
