<?php

namespace Drupal\single_content_sync\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\ByteSizeMarkup;
use Drupal\single_content_sync\ContentBatchImporter;
use Drupal\single_content_sync\ContentImporterInterface;
use Drupal\single_content_sync\ContentSyncHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to import a content.
 *
 * @package Drupal\single_content_sync\Form
 */
class ContentImportForm extends FormBase {

  /**
   * The content importer service.
   *
   * @var \Drupal\single_content_sync\ContentImporterInterface
   */
  protected ContentImporterInterface $contentImporter;

  /**
   * The content sync helper.
   *
   * @var \Drupal\single_content_sync\ContentSyncHelperInterface
   */
  protected ContentSyncHelperInterface $contentSyncHelper;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * ContentImportForm constructor.
   *
   * @param \Drupal\single_content_sync\ContentImporterInterface $content_importer
   *   The content importer service.
   * @param \Drupal\single_content_sync\ContentSyncHelperInterface $content_sync_helper
   *   The content sync helper.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ContentImporterInterface $content_importer, ContentSyncHelperInterface $content_sync_helper, LanguageManagerInterface $language_manager) {
    $this->contentImporter = $content_importer;
    $this->contentSyncHelper = $content_sync_helper;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('single_content_sync.importer'),
      $container->get('single_content_sync.helper'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_content_sync_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $validators = [];

    // Still support older drupal version than 10.2 with old file extension
    // validator approach.
    if (floatval(\Drupal::VERSION) < 10.2) {
      $validators['file_validate_extensions'] = ['zip yml'];
      // @phpstan-ignore-next-line
      $max_upload_size = format_size(Environment::getUploadMaxSize());
    }
    else {
      $validators['FileExtension']['extensions'] = 'zip yml';
      $max_upload_size = ByteSizeMarkup::create(Environment::getUploadMaxSize());
    }

    $schema = $this->contentSyncHelper->getImportDirectorySchema();

    $form['upload_fid'] = [
      '#type' => 'managed_file',
      '#upload_location' => "{$schema}://import/zip",
      '#upload_validators' => $validators,
      '#title' => $this->t('Upload a file with content to import'),
      '#description' => $this->t(
        'Upload a Zip or YAML file with the previously exported content. Maximum file size: @size.',
        ['@size' => $max_upload_size]
      ),
    ];
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paste the content from the clipboard'),
      '#required' => FALSE,
      '#prefix' => '<br><p>' . $this->t('OR') . '</p><br>',
      '#attributes' => [
        'data-yaml-editor' => 'true',
      ],
      '#attached' => [
        'library' => [
          'single_content_sync/yaml_editor',
        ],
      ],
    ];
    $languages = ['all' => $this->t('All languages (import all translations)')];
    foreach ($this->languageManager->getLanguages() as $language) {
      $languages[$language->getId()] = $language->getName();
    }
    $form['langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Import language'),
      '#options' => $languages,
      '#default_value' => 'all',
      '#description' => $this->t('Preserve all translations in the file, or import its primary content and references into one selected language.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $upload_file = $form_state->getValue('upload_fid');
    $content = $form_state->getValue('content');

    if (!$upload_file && !$content) {
      $form_state->setErrorByName('upload_fid' & 'content', $this->t('Please fill in one of the fields to import your content.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $langcode = $form_state->getValue('langcode', 'all');

    // Handle uploaded file first.
    if ($upload_file = $form_state->getValue('upload_fid')) {
      $fid = reset($upload_file);
      $file_real_path = $this->contentSyncHelper->getFileRealPathById($fid);
      $file_info = pathinfo($file_real_path);
      $entity = NULL;

      try {
        if ($file_info['extension'] === 'zip') {
          $this->contentImporter->importFromZip($file_real_path, $langcode);
        }
        else {
          $entity = $this->contentImporter->importFromFile($file_real_path, $langcode);
        }
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
      }

      // Clean up the temporary uploaded file.
      ContentBatchImporter::cleanUploadedFile($fid);
    }
    else {
      try {
        $content_array = $this->contentSyncHelper->validateYamlFileContent($form_state->getValue('content'));
        $entity = $this->contentImporter->doImport($content_array, $langcode);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($e->getMessage());
        return;
      }
    }

    if ($entity) {
      $this->messenger()->addStatus($this->t('The content has been synced @link', [
        '@link' => $entity->toLink()->toString(),
      ]));
    }
  }

}
