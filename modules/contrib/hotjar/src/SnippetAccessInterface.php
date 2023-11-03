<?php

namespace Drupal\hotjar;

/**
 * Snippet access service interface.
 *
 * @package Drupal\hotjar
 */
interface SnippetAccessInterface {

  /**
   * Determines whether we add tracking code to page.
   *
   * @return bool
   *   Return TRUE if user can access snippet.
   */
  public function check();

}
