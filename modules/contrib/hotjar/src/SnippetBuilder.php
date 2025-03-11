<?php

namespace Drupal\hotjar;

use Drupal\Component\Utility\Html;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Snippet builder service.
 *
 * @package Drupal\hotjar
 */
class SnippetBuilder implements SnippetBuilderInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * State storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Hotjar settings.
   *
   * @var \Drupal\hotjar\HotjarSettingsInterface
   */
  protected $settings;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * JS collection optimizer.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The file handler under test.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * SnippetBuilder constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Store storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\hotjar\HotjarSettingsInterface $settings
   *   Hotjar settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   JS assets optimizer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file handler.
   */
  public function __construct(
    StateInterface $state,
    ConfigFactoryInterface $config_factory,
    HotjarSettingsInterface $settings,
    ModuleHandlerInterface $module_handler,
    AssetCollectionOptimizerInterface $js_collection_optimizer,
    MessengerInterface $messenger,
    FileSystemInterface $file_system,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
    $this->moduleHandler = $module_handler;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('hotjar.settings'),
      $container->get('module_handler'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('messenger'),
      $container->get('file_system'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function pageAttachment(array &$attachments) {
    // Do not attach Hotjar if no Hotjar ID was provided.
    if (empty($this->settings->getSetting('account'))) {
      return;
    }

    if ($this->settings->getSetting('attachment_mode') === HotjarSettingsInterface::ATTACHMENT_MODE_DRUPAL_SETTINGS) {
      $this->pageAttachmentDrupalSettings($attachments);
    }
    else {
      $this->pageAttachmentBuilt($attachments);
    }
  }

  /**
   * Add page attachments when Build mode is in use.
   */
  protected function pageAttachmentBuilt(array &$attachments) {
    $uri = $this->getSnippetPath();
    $query_string = $this->state->get('system.css_js_query_string') ?: '0';
    $query_string_separator = (strpos($uri, '?') !== FALSE) ? '&' : '?';

    $url = $this->fileUrlGenerator->generateString($uri);
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['src' => $url . $query_string_separator . $query_string],
      ],
      'hotjar_script_tag',
    ];
  }

  /**
   * Add page attachments when DrupalSettings mode is in use.
   */
  protected function pageAttachmentDrupalSettings(array &$attachments) {
    // Assets resolver will escape drupalSettings.
    $clean_id = Html::escape((string) $this->settings->getSetting('account'));
    $clean_version = Html::escape($this->settings->getSetting('snippet_version'));

    $attachments['#attached']['drupalSettings']['hotjar']['account'] = $clean_id;
    $attachments['#attached']['drupalSettings']['hotjar']['snippetVersion'] = $clean_version;
    $attachments['#attached']['library'][] = 'hotjar/hotjar';
  }

  /**
   * {@inheritdoc}
   */
  public function createAssets() {
    $this->settings->getSettings(TRUE);
    $result = TRUE;
    $directory = dirname($this->getSnippetPath());
    if (!is_dir($directory) || !is_writable($directory)) {
      $result = $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    }
    if ($result) {
      $result = $this->saveSnippets();
    }
    else {
      $this->messenger->addWarning($this->t('Failed to create or make writable the directory %directory, possibly due to a permissions problem. Make the directory writable.', ['%directory' => $directory]));
    }
    return $result;
  }

  /**
   * Saves JS snippet files based on current settings.
   *
   * @return bool
   *   Whether the files were saved.
   */
  protected function saveSnippets() {
    $snippet = $this->buildSnippet();
    $snippet_path = $this->getSnippetPath();
    if ($this->fileSystem->realpath($snippet_path)) {
      $this->fileSystem->delete($snippet_path);
    }

    $dir = $this->fileSystem->realpath(dirname($snippet_path));
    $this->fileSystem->prepareDirectory(
      $dir,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );
    $path = $this->fileSystem->saveData(
      $snippet,
      $snippet_path,
      FileSystemInterface::EXISTS_REPLACE
    );

    if ($path === FALSE) {
      $this->messenger->addMessage($this->t('An error occurred saving one or more snippet files. Please try again or contact the site administrator if it persists.'));
      return FALSE;
    }

    $this->messenger->addMessage($this->t('Created snippet file based on configuration.'));
    $this->jsCollectionOptimizer->deleteAll();
    _drupal_flush_css_js();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSnippet() {
    $id = $this->settings->getSetting('account');
    // Use escaped HotjarID.
    $clean_id = $this->escapeValue($id);
    $clean_version = $this->escapeValue($this->settings->getSetting('snippet_version'));

    // Quote from the Hotjar dashboard:
    // The Tracking Code below should be placed in the <head> tag of
    // every page you want to track on your site.
    if ($id && $clean_id) {
      $script = <<<HJ
(function(h,o,t,j,a,r){
  h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
  h._hjSettings={hjid:{$clean_id},hjsv:{$clean_version}};
  a=o.getElementsByTagName('head')[0];
  r=o.createElement('script');r.async=1;
  r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
  a.appendChild(r);
})(window,document,'//static.hotjar.com/c/hotjar-','.js?sv=');
HJ;
    }
    else {
      $script = <<<HJ
// Empty HotjarID.
HJ;
    }

    // Allow other modules to modify or wrap the script.
    $this->moduleHandler->alter('hotjar_snippet', $script);

    // Compact script if core aggregation or advagg module are enabled.
    if (
      $this->isJsPreprocessEnabled()
      || $this->moduleHandler->moduleExists('advagg')
    ) {
      $script = str_replace(["\n", '  '], '', $script);
    }

    return $script;
  }

  /**
   * Escape value.
   */
  protected function escapeValue($value) {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
  }

  /**
   * Checks if JS preprocess is enabled.
   *
   * @return bool
   *   Returns TRUE if JS preprocess is enabled.
   */
  protected function isJsPreprocessEnabled() {
    $config = $this->configFactory->get('system.performance');
    $configured = $config->get('js.preprocess');
    if (!isset($configured)) {
      $configured = TRUE;
    }
    return $configured === TRUE;
  }

  /**
   * Get snippet path.
   *
   * @return string
   *   Path to snippet.
   */
  protected function getSnippetPath() {
    return $this->settings->getSetting('snippet_path');
  }

}
