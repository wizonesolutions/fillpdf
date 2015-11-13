<?php

/**
 * @file
 * Contains \Drupal\fillpdf\TokenResolver.
 */

namespace Drupal\fillpdf;

use Drupal\Core\Utility\Token;

/**
 * Class TokenResolver.
 *
 * @package Drupal\fillpdf
 */
class TokenResolver implements TokenResolverInterface {

  /**
   * Drupal\Core\Utility\Token definition.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($original, array $entities) {
    // Whichever entity matches the token last wins.
    $replaced_string = $original;

    foreach ($entities as $entity_type => $entity_objects) {
      // Our rule is "last value wins." So use the last entities's values first.
      $seititne = array_reverse($entity_objects); // Get it?

      foreach ($seititne as $entity_id => $entity) {
        $replaced_string = $this->token->replace($replaced_string, [
          $entity_type => $entity,
        ]);
      }
    }

    return $this->token->replace($replaced_string, [], ['clear' => TRUE]);
  }

}
