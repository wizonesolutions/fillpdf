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
    $replaced_string = '';

    // TODO: Do these in reverse order, and only apply the value when the token gets replaced with something. Probably have to use Token::scan() myself because otherwise I'm not sure if I will know if any tokens matched or not. Maybe I can pass in the third argument and check what the BubbleableMetadata says? Check my StackExchange question about this.
    foreach ($entities as $entity_type => $entity_objects) {
      foreach ($entity_objects as $entity_id => $entity) {
        // @todo: Refactor so the token context can be re-used for title replacement later OR do title replacement here so that it works the same way as value replacement and doesn't just use the last value...like it does in Drupal 7 :(
        // @todo: What if one fill pattern has tokens from multiple types in it? Figure out the best way to deal with that and rewrite this section accordingly. Probably some form of parallel arrays. Basically we'd have to run all combinations, although our logic still might not be smart enough to tell if *all* tokens in the source text have been replaced, or in which case both of them have been replaced last (which is what we want). I could deliberately pass each entity context separately and then count how many of them match, and only overwrite it if the match count is higher than the current one. Yeah, that's kind of inefficient but also a good start. I might just be able to scan for tokens myself and then check if they're still in the $uncleaned_base output, or do the cleaning myself so I only have to call Token::replace once. TBD.
        $maybe_replaced_string = $this->token->replace($original, [
          $entity_type => $entity,
        ], [
          'clean' => TRUE,
        ]);
        // Generate a non-cleaned version of the token string so we can
        // tell if the non-empty string we got back actually replaced
        // some tokens.
        $uncleaned_base = $this->token->replace($original, [
          $entity_type => $entity,
        ]);

        // If we got a result that isn't what we put in, update the value
        // for this field..
        if ($maybe_replaced_string && $original !== $uncleaned_base) {
          $replaced_string = $maybe_replaced_string;
        }
      }
    }

    return $replaced_string;
  }

}
