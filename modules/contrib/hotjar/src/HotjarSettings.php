<?php

namespace Drupal\hotjar;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hotjar settings service.
 *
 * @package Drupal\hotjar
 */
class HotjarSettings implements HotjarSettingsInterface, ContainerInjectionInterface {

  /**
   * Hotjar config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * HotjarSettings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->config = $config_factory->get('hotjar.settings');
    $this->moduleHandler = $module_handler;
    $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($force = FALSE) {
    if (!$this->settings || $force) {
      $settings = (array) $this->config->getOriginal();
      $settings += [
        'account' => NULL,
        'snippet_version' => HotjarSettingsInterface::HOTJAR_SNIPPET_VERSION,
        'snippet_path' => HotjarSettingsInterface::HOTJAR_SNIPPET_PATH,
        'attachment_mode' => HotjarSettingsInterface::ATTACHMENT_MODE_BUILD,
        'visibility_pages' => 0,
        'pages' => HotjarSettingsInterface::HOTJAR_PAGES,
        'visibility_roles' => 0,
        'roles' => [],
      ];

      if (empty($settings['attachment_mode'])) {
        $settings['attachment_mode'] = HotjarSettingsInterface::ATTACHMENT_MODE_BUILD;
      }
      if (empty($settings['snippet_version'])) {
        $settings['snippet_version'] = HotjarSettingsInterface::HOTJAR_SNIPPET_VERSION;
      }
      if (empty($settings['snippet_path'])) {
        $settings['snippet_path'] = HotjarSettingsInterface::HOTJAR_SNIPPET_PATH;
      }

      $this->moduleHandler->alter('hotjar_settings', $settings);
      $this->settings = $settings;
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = NULL) {
    $this->getSettings();
    return array_key_exists($key, $this->settings) ? $this->settings[$key] : $default;
  }

}
