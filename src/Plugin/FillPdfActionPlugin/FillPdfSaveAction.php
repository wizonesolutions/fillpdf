<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Plugin\FillPdfActionPlugin\FillPdfSaveAction.
 */

namespace Drupal\fillpdf\Plugin\FillPdfActionPlugin;

use Drupal\Core\Annotation\Translation;
use Drupal\fillpdf\Annotation\FillPdfActionPlugin;
use Drupal\fillpdf\OutputHandler;
use Drupal\fillpdf\Plugin\FillPdfActionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class FillPdfSaveAction
 * @package Drupal\fillpdf\Plugin\FillPdfActionPlugin
 *
 * @FillPdfActionPlugin(
 *   id = "save",
 *   label = @Translation("Save PDF to file")
 * )
 */
class FillPdfSaveAction extends FillPdfActionPluginBase {

  /** @var OutputHandler $outputHandler */
  protected $outputHandler;

  public function __construct(OutputHandler $output_handler, array $configuration, $plugin_id, $plugin_definition) {
    $this->outputHandler = $output_handler;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('fillpdf.output_handler'), $configuration, $plugin_id, $plugin_definition);
  }

  public function execute() {
    // @todo: Error handling?
    $this->outputHandler->savePdfToFile($this->configuration);

    // @todo: Fix based on value of post_save_redirect, once I add that
    $response = new RedirectResponse('/');
    return $response;
  }

}
