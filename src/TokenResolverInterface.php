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
   * @return string
   * The passed-in string after replacing all possible tokens. The default
   * implementation of this interface removes any non-matched tokens.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   * @see TokenResolver
   */
  public function replace($original, array $entities);

}
