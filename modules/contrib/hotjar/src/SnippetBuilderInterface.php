<?php

namespace Drupal\hotjar;

/**
 * Snippet builder service interface.
 *
 * @package Drupal\hotjar
 */
interface SnippetBuilderInterface {

  /**
   * Prepares directory for and saves snippet files based on current settings.
   *
   * @return bool
   *   Whether the files were saved.
   */
  public function createAssets();

  /**
   * Implements hook_page_attachment().
   */
  public function pageAttachment(array &$attachments);

  /**
   * Get Hotjar snippet code.
   *
   * @return mixed|string
   *   Hotjar snippet.
   */
  public function buildSnippet();

}
