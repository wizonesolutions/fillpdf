<?php

/**
 * @file
 * Contains \Drupal\fillpdf\TokenResolverInterface.
 */

namespace Drupal\fillpdf;

/**
 * Provides consistent token replacement for one or multiple entity sources.
 *
 * @package Drupal\fillpdf
 */
interface TokenResolverInterface {

  /**
   * @param string $original
   * The string containing the tokens to replace.
   *
   * @param array $entities
   * An array of entities to be used as arguments to Token::replace
   *
   * @param $replace_options
   * @return string
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function replace($original, array $entities, $replace_options);

}
