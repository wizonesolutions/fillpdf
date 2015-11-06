<?php
/**
 * @file
 * Contains \Drupal\fillpdf\Service\FillPdfLinkManipulator.
 */

namespace Drupal\fillpdf\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\fillpdf\FillPdfLinkManipulatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritDoc}
 */
class FillPdfLinkManipulator implements FillPdfLinkManipulatorInterface {

  /**
   * @param Request $request The request containing the query string to parse.
   * @return array
   *
   * @todo: Maybe this should return a FillPdfLinkContext object or something?
   *   Guess it depends on how much I end up needing to change it.
   */
  public function parseRequest(Request $request) {
    // @todo: Use Url::fromRequest when/if it lands in core. See https://www.drupal.org/node/2605530
    $path = $request->getUri();
    $request_url = $this->createUrlFromString($path);

    return $this->parseLink($request_url);
  }

  /**
   * {@inheritDoc}
   */
  public function parseLink(Url $link) {
    $query = $link->getOption('query');

    if (!$query) {
      throw new \InvalidArgumentException('The \Drupal\Core\Url you pass in must
      have its \'query\' option set.');
    }

    $request_context = [
      'entity_ids' => NULL,
      'fid' => NULL,
      'sample' => NULL,
      'force_download' => FALSE,
      'flatten' => TRUE,
    ];

    if (!empty($query['sample'])) {
      $sample = TRUE;
    }
    $request_context['sample'] = $sample; // is this just the PDF populated with sample data?


    if (!empty($query['fid'])) {
      $request_context['fid'] = $query['fid'];
    }

    if (!empty($query['entity_type'])) {
      $request_context['entity_type'] = $query['entity_type'];
    }

    $request_context['entity_ids'] = $entity_ids = [];
    if (!empty($query['entity_id']) || !empty($query['entity_ids'])) {
      $entity_ids = (!empty($query['entity_id']) ? [$query['entity_id']] : $query['entity_ids']);

      // Re-key entity IDs so they can be loaded easily with loadMultiple().
      // If we have type information, add it to the types array, and remove it
      // in order to make sure we only store the ID in the entity_ids key.
      foreach ($entity_ids as $entity_id) {
        $entity_id_parts = explode(':', $entity_id);

        if (count($entity_id_parts) == 2) {
          $entity_type = $entity_id_parts[0];
          $entity_id = $entity_id_parts[1];
        }
        elseif (!empty($request_context['entity_type'])) {
          $entity_type = $request_context['entity_type'];
        }
        else {
          $entity_type = 'node';
        }
        $request_context['entity_ids'] += [
          $entity_type => [],
        ];

        $request_context['entity_ids'][$entity_type][$entity_id] = $entity_id;
      }
    }

    // We've processed the shorthand forms, so unset them.
    unset($request_context['entity_id'], $request_context['entity_type']);

    if (!$query['download'] && (int) $query['download'] == 1) {
      $request_context['force_download'] = TRUE;
    }
    if ($query['flatten'] && (int) $query['flatten'] == 0) {
      $request_context['flatten'] = FALSE;
    }

    return $request_context;
  }

  public function parseUrlString($url) {
    $link = $this->createUrlFromString($url);
    return $this->parseLink($link);
  }

  /**
   * {@inheritdoc}
   */
  public function generateLink(array $parameters) {
    $query = [];

    if (!isset($parameters['fid'])) {
      throw new \InvalidArgumentException("The $parameters argument must contain the fid key (the FillPdfForm's ID).");
    }

    $query['fid'] = $parameters['fid'];

    // Only set the following properties if they're not at their default values.
    // This makes the resulting Url a bit cleaner.
    // Structure:
    //   '<key in context array>' => [
    //     ['<key in query string>', <default system value>]
    //     ...
    //   ]
    // @todo: Create a value object for FillPdfMergeContext and get the defaults here from that
    $parameter_info = [
      'sample' => ['sample', FALSE],
      'force_download' => ['download', FALSE],
      'flatten' => ['flatten', TRUE],
    ];
    foreach ($parameter_info as $context_key => $info) {
      $query_key = $info[0];
      $parameter_default = $info[1];
      if (isset($parameters[$context_key]) && $parameters[$context_key] != $parameter_default) {
        $query[$query_key] = $parameters[$context_key];
      }
    }

    // $query['entity_ids'] contains entity IDs indexed by entity type.
    // Collapse these into the entity_type:entity_id format.
    $query['entity_ids'] = [];
    $entity_info = $parameters['entity_ids'];
    foreach ($entity_info as $entity_type => $entity_ids) {
      foreach ($entity_ids as $entity_id) {
        $query['entity_ids'][] = "{$entity_type}:{$entity_id}";
      }
    }

    $fillpdf_link = Url::fromRoute('fillpdf.populate_pdf',
      [],
      ['query' => $query]);

    return $fillpdf_link;
  }

  /**
   * @param $url
   *
   * @see FillPdfLinkManipulatorInterface::parseUrlString()
   *
   * @return \Drupal\Core\Url
   */
  protected function createUrlFromString($url) {
    $url_parts = UrlHelper::parse($url);
    $path = $url_parts['path'];
    $query = $url_parts['query'];

    $link = Url::fromUri($path, ['query' => $query]);
    return $link;
  }

}
