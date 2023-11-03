<?php

namespace Drupal\hotjar;

/**
 * Hotjar settings service interface.
 *
 * @package Drupal\hotjar
 */
interface HotjarSettingsInterface {

  const HOTJAR_SNIPPET_VERSION = 6;
  const HOTJAR_SNIPPET_PATH = 'public://hotjar/hotjar.script.js';
  const HOTJAR_PAGES = "/admin\n/admin/*\n/batch\n/node/add*\n/node/*/*\n/user/*/*";

  const ATTACHMENT_MODE_BUILD = 'build';
  const ATTACHMENT_MODE_DRUPAL_SETTINGS = 'drupalSettings';

  /**
   * Get all settings.
   *
   * @param bool $force
   *   Force update of settings.
   *
   * @return array
   *   Get settings.
   */
  public function getSettings($force = FALSE);

  /**
   * Get setting.
   *
   * @param string $key
   *   Settings key.
   * @param mixed $default
   *   Default value.
   *
   * @return mixed
   *   Setting value.
   */
  public function getSetting($key, $default = NULL);

}
