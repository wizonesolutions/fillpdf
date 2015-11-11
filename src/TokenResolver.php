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
   * @var Drupal\Core\Utility\Token
   */
  protected $token;
  /**
   * Constructor.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

}
