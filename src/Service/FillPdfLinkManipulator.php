<?php
/**
  * @file
  * Contains \Drupal\fillpdf\Service\FillPdfLinkManipulator.
  */

namespace Drupal\fillpdf\Service;

use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\HttpFoundation\Request;

class FillPdfLinkManipulator implements FillPdfLinkManipulatorInterface {

  /**
   * @param Request $request The request containing the query string to parse.
   * @return array
   *
   * @todo: Maybe this should return a FillPdfLinkContext object or something?
   *   Guess it depends on how much I end up needing to change it.
   */
  public function parseLink(Request $request) {
    $request_context = array(
      'entity_ids' => NULL,
      'fid' => NULL,
      'sample' => NULL,
      'force_download' => FALSE,
      'flatten' => TRUE,
    );

    $request_context['sample'] = $request->get('sample'); // is this just the PDF populated with sample data?
    $request_context['fid'] = $request->get('fid');

    if ($request->get('entity_type')) {
      $request_context['entity_type'] = $request->get('entity_type');
    }

    $request_context['entity_ids'] = $entity_ids = array();
    if ($request->get('entity_id') || $request->get('entity_ids')) {
      $entity_ids = ($request->get('entity_id') ? array($request->get('entity_id')) : $request->get('entity_ids'));

      // Re-key entity IDs so they can be loaded easily with loadMultiple().
      // If we have type information, add it to the types array, and remove it
      // make sure we only store the ID in the entity_ids key.
      foreach ($entity_ids as $entity_id) {
        $entity_id_parts = explode(':', $entity_id);

        if (count($entity_id_parts) == 2) {
          $entity_type = $entity_id_parts[0];
          $entity_id = $entity_id_parts[1];
        }
        else {
          $entity_type = 'node';
        }
        $request_context['entity_ids'] += array(
          $entity_type => array(),
        );

        $request_context['entity_ids'][$entity_type][$entity_id] = $entity_id;
      }
    }

    if ($request->get('download') && (int) $request->get('download') == 1) {
      $request_context['force_download'] = TRUE;
    }
    if ($request->get('flatten') && (int) $request->get('flatten') == 0) {
      $request_context['flatten'] = FALSE;
    }

    return $request_context;
  }

  /**
   * @param array $parameters
   *   The array of parameters to be converted into a
   *     URL and query string.
   * @return string
   */
  public function generateLink(array $parameters) {
    // TODO: Implement generateLink() method.
  }

}
