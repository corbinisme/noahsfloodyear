<?php

namespace Drupal\Tests\single_content_sync\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\single_content_sync\ContentBatchImporter;
use Drupal\single_content_sync\Form\ContentImportForm;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Tests multilingual exports and imports, including nested references.
 *
 * @group single_content_sync
 */
class MultilingualContentSyncTest extends ContentImporterTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'single_content_sync',
    'content_translation',
    'field',
    'file',
    'filter',
    'language',
    'link',
    'menu_link_content',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('menu_link_content');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['language', 'single_content_sync']);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->container->get('content_translation.manager')->setEnabled('node', 'article', TRUE);
    $body = FieldConfig::loadByName('node', 'article', 'body');
    $body->setTranslatable(TRUE)->save();
    $this->addField('field_reference', 'entity_reference', ['target_type' => 'node']);
    $this->addField('field_link', 'link');
  }

  /**
   * Creates a translatable field for test nodes.
   */
  protected function addField(string $name, string $type, array $settings = []): void {
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => $name,
      'type' => $type,
      'settings' => $settings,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'article',
      'field_name' => $name,
      'translatable' => TRUE,
    ])->save();
  }

  /**
   * Creates a translated node whose default language differs from the context.
   */
  protected function createTranslatedNode(): Node {
    $node = Node::create([
      'type' => 'article',
      'langcode' => 'es',
      'title' => 'Spanish title',
      'body' => ['value' => 'Spanish body', 'format' => 'plain_text'],
    ]);
    $node->addTranslation('en', [
      'title' => 'English title',
      'body' => ['value' => 'English body', 'format' => 'plain_text'],
    ]);
    $node->addTranslation('fr', [
      'title' => 'French title',
      'body' => ['value' => 'French body', 'format' => 'plain_text'],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Base and custom fields both reach the requested translation.
   */
  public function testUpdateExistingTranslation(): void {
    $node = $this->createTranslatedNode();
    $content = $this->getTestNodeData($node->uuid(), 'Updated English title', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $imported = $this->getImporter()->doImport($content);
    $this->assertSame('en', $imported->language()->getId());
    $stored = Node::load($node->id());
    $this->assertSame('Spanish title', $stored->getTitle());
    $this->assertSame('Spanish body', $stored->body->value);
    $this->assertSame('Updated English title', $stored->getTranslation('en')->getTitle());
    $this->assertSame('test node body', $stored->getTranslation('en')->body->value);
    $this->assertSame('French body', $stored->getTranslation('fr')->body->value);
  }

  /**
   * A missing translation is created without changing the original language.
   */
  public function testAddMissingTranslation(): void {
    $node = Node::create(['type' => 'article', 'langcode' => 'es', 'title' => 'Original']);
    $node->save();
    $content = $this->getTestNodeData($node->uuid(), 'New French title', TRUE);
    $content['base_fields']['langcode'] = 'fr';
    $this->getImporter()->doImport($content);
    $stored = Node::load($node->id());
    $this->assertSame('es', $stored->language()->getId());
    $this->assertSame('Original', $stored->getTitle());
    $this->assertSame('New French title', $stored->getTranslation('fr')->getTitle());
    $this->assertSame('test node body', $stored->getTranslation('fr')->body->value);
  }

  /**
   * Translation selection is independent of the translation form settings.
   */
  public function testImportWithTranslationUiDisabled(): void {
    $node = $this->createTranslatedNode();
    $this->container->get('content_translation.manager')->setEnabled('node', 'article', FALSE);
    $content = $this->getTestNodeData($node->uuid(), 'Updated French title', TRUE);
    $content['base_fields']['langcode'] = 'fr';
    $imported = $this->getImporter()->doImport($content);
    $this->assertSame('fr', $imported->language()->getId());
    $this->assertSame('Updated French title', $imported->getTitle());
    $this->assertSame('Spanish title', $imported->getUntranslated()->getTitle());
  }

  /**
   * All-language import skips unknown translations, even on nested entities.
   */
  public function testImportAllLanguagesWithUnknownTranslation(): void {
    $uuid = $this->container->get('uuid');
    $content = $this->getTestNodeData($uuid->generate(), 'English parent', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $child = $this->getTestNodeData($uuid->generate(), 'English child', TRUE);
    $child['base_fields']['langcode'] = 'en';
    foreach ([&$content, &$child] as &$item) {
      $french = $item;
      $french['base_fields']['langcode'] = 'fr';
      $french['base_fields']['title'] = 'French title';
      $french['custom_fields']['body'][0]['value'] = 'French body';
      $item['translations']['de'] = $french;
      $item['translations']['fr'] = $french;
    }
    unset($item);
    $content['custom_fields']['field_reference'] = [$child];
    $node = $this->getImporter()->doImport($content, 'all');
    $this->assertSame('English parent', $node->getTitle());
    $this->assertSame('French title', $node->getTranslation('fr')->getTitle());
    $this->assertSame('French body', $node->getTranslation('fr')->body->value);
    $this->assertFalse($node->hasTranslation('de'));
    $referenced = $node->field_reference->entity;
    $this->assertSame('English child', $referenced->getTitle());
    $this->assertTrue($referenced->hasTranslation('fr'));
    $this->assertFalse($referenced->hasTranslation('de'));
    $errors = $this->container->get('messenger')->messagesByType('error');
    $this->assertStringContainsString('de translation was skipped', (string) reset($errors));
  }

  /**
   * Imports content and references in one language and skips other languages.
   */
  public function testImportSelectedLanguage(): void {
    $uuid = $this->container->get('uuid');
    $content = $this->getTestNodeData($uuid->generate(), 'Primary title', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $translation = $content;
    $translation['base_fields']['title'] = 'Other translation';
    $translation['base_fields']['langcode'] = 'fr';
    $content['translations']['fr'] = $translation;
    $child = $this->getTestNodeData($uuid->generate(), 'Primary child', TRUE);
    $child['base_fields']['langcode'] = 'es';
    $child['translations']['de'] = $translation;
    $content['custom_fields']['field_reference'] = [$child];
    $node = $this->getImporter()->doImport($content, 'fr');
    $this->assertSame('fr', $node->language()->getId());
    $this->assertSame('Primary title', $node->getTitle());
    $this->assertSame('fr', $node->field_reference->entity->language()->getId());
    $this->assertCount(1, $node->getTranslationLanguages());
    $this->assertCount(1, $node->field_reference->entity->getTranslationLanguages());
    $this->assertEmpty($this->container->get('messenger')->messagesByType('error'));
  }

  /**
   * An unavailable primary language uses a configured translation when present.
   */
  public function testUnavailablePrimaryLanguage(): void {
    $content = $this->getTestNodeData($this->container->get('uuid')->generate(), 'Unavailable title', TRUE);
    $content['base_fields']['langcode'] = 'de';
    $translation = $content;
    $translation['base_fields']['title'] = 'French title';
    $content['translations']['fr'] = $translation;
    $node = $this->getImporter()->doImport($content);
    $this->assertSame('fr', $node->language()->getId());
    $this->assertSame('French title', $node->getTitle());
  }

  /**
   * A new reference with no available language still produces a valid entity.
   */
  public function testUnavailableNewReferenceLanguage(): void {
    $content = $this->getTestNodeData($this->container->get('uuid')->generate(), 'Parent', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $child = $this->getTestNodeData($this->container->get('uuid')->generate(), 'Child', TRUE);
    $child['base_fields']['langcode'] = 'de';
    $content['custom_fields']['field_reference'] = [$child];
    $node = $this->getImporter()->doImport($content);
    $this->assertFalse($node->isNew());
    $this->assertSame('en', $node->field_reference->entity->language()->getId());
    $this->assertSame('Child', $node->field_reference->entity->getTitle());
    $this->assertNotEmpty($this->container->get('messenger')->messagesByType('warning'));
  }

  /**
   * Unavailable primary content cannot overwrite an existing translation.
   */
  public function testUnavailableLanguageLeavesExistingContent(): void {
    $node = $this->createTranslatedNode();
    $content = $this->getTestNodeData($node->uuid(), 'Unavailable title', TRUE);
    $content['base_fields']['langcode'] = 'de';
    $this->getImporter()->doImport($content);
    $stored = Node::load($node->id());
    $this->assertSame('Spanish title', $stored->getTitle());
    $this->assertSame('English body', $stored->getTranslation('en')->body->value);
  }

  /**
   * Entities and references use the language context and separate caches.
   */
  public function testExportReferencesAndLanguageCache(): void {
    $child = $this->createTranslatedNode();
    $node = $this->createTranslatedNode();
    foreach (['en', 'fr'] as $langcode) {
      $node->getTranslation($langcode)->set('field_reference', [$child, $child]);
    }
    $node->save();
    $exporter = $this->container->get('single_content_sync.exporter');
    $output = $exporter->doExportToArray($node);
    $this->assertSame('en', $output['base_fields']['langcode']);
    $this->assertSame('English title', $output['custom_fields']['field_reference'][0]['base_fields']['title']);
    // Export the field again to check cached reference stubs.
    $stub = $exporter->getFieldValue($node->getTranslation('en')->field_reference);
    $this->assertSame('en', $stub[0]['base_fields']['langcode']);
    $this->assertArrayNotHasKey('custom_fields', $stub[0]);
    $french = $exporter->getFieldValue($node->getTranslation('fr')->field_reference);
    $this->assertSame('French title', $french[0]['base_fields']['title']);
    $this->assertSame('French body', $french[0]['custom_fields']['body'][0]['value']);
  }

  /**
   * All-translations exports resolve references in each translation.
   */
  public function testExportAllTranslations(): void {
    $child = $this->createTranslatedNode();
    $node = $this->createTranslatedNode();
    foreach (['en', 'fr', 'es'] as $langcode) {
      $node->getTranslation($langcode)->set('field_reference', [$child]);
    }
    $node->save();
    $output = Yaml::decode($this->container->get('single_content_sync.exporter')->doExportToYml($node, TRUE));
    $this->assertSame('en', $output['base_fields']['langcode']);
    $this->assertSame('English title', $output['custom_fields']['field_reference'][0]['base_fields']['title']);
    $this->assertSame('French title', $output['translations']['fr']['custom_fields']['field_reference'][0]['base_fields']['title']);
    $this->assertSame('Spanish title', $output['translations']['es']['custom_fields']['field_reference'][0]['base_fields']['title']);
  }

  /**
   * Links in stub and full mode resolve translated targets.
   */
  public function testExportLinks(): void {
    $child = $this->createTranslatedNode();
    $node = $this->createTranslatedNode()->getTranslation('en');
    $node->set('field_link', [['uri' => 'entity:node/' . $child->id()]]);
    $node->set('body', [[
      'value' => '<a href="/node/' . $child->id() . '" data-entity-type="node" data-entity-uuid="' . $child->uuid() . '">Link</a>',
      'format' => 'plain_text',
    ],
    ]);
    $exporter = $this->container->get('single_content_sync.exporter');
    foreach (['stub', 'full'] as $mode) {
      $this->config('single_content_sync.settings')->set('embedded_entities_export_mode', $mode)->save();
      $exporter->resetCache();
      $link = $exporter->getFieldValue($node->field_link);
      $this->assertSame('English title', $link[0]['linked_entity']['base_fields']['title']);
      $exporter->resetCache();
      $body = $exporter->getFieldValue($node->body);
      $this->assertSame('English title', $body[0]['embed_entities'][0]['base_fields']['title']);
    }
  }

  /**
   * Taxonomy and menu parents and targets use their owner's language.
   */
  public function testExportBaseFieldReferences(): void {
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();
    $this->container->get('content_translation.manager')->setEnabled('taxonomy_term', 'tags', TRUE);
    $parent = Term::create(['vid' => 'tags', 'langcode' => 'es', 'name' => 'Spanish parent']);
    $parent->addTranslation('en', ['name' => 'English parent']);
    $parent->save();
    $term = Term::create(['vid' => 'tags', 'langcode' => 'en', 'name' => 'Child', 'parent' => $parent->id()]);
    $term->save();
    $exporter = $this->container->get('single_content_sync.exporter');
    $base = $exporter->exportBaseValues($term);
    $this->assertSame('English parent', $base['parent']['base_fields']['name']);
    $term->setSyncing(TRUE);
    $exporter->resetCache();
    $base = $exporter->exportBaseValues($term);
    $this->assertSame('English parent', $base['parent']['base_fields']['name']);

    $this->container->get('content_translation.manager')->setEnabled('menu_link_content', 'menu_link_content', TRUE);
    $node = $this->createTranslatedNode();
    $menu_parent = MenuLinkContent::create([
      'title' => 'Spanish menu',
      'langcode' => 'es',
      'menu_name' => 'main',
      'link' => ['uri' => 'entity:node/' . $node->id()],
    ]);
    $menu_parent->addTranslation('en', ['title' => 'English menu']);
    $menu_parent->save();
    $menu_child = MenuLinkContent::create([
      'title' => 'Child',
      'langcode' => 'en',
      'menu_name' => 'main',
      'parent' => $menu_parent->getPluginId(),
      'link' => ['uri' => 'entity:node/' . $node->id()],
    ]);
    $menu_child->save();
    $base = $exporter->exportBaseValues($menu_child);
    $this->assertSame('English menu', $base['parent']['base_fields']['title']);
    $this->assertSame('English title', $base['link'][0]['entity']['base_fields']['title']);
  }

  /**
   * The helper and import form expose the correct language behavior.
   */
  public function testLanguageHelperAndForm(): void {
    $node = $this->createTranslatedNode();
    $entity = $this->container->get('single_content_sync.helper')->getDefaultLanguageEntity(new ParameterBag(['node' => $node]));
    $this->assertSame('en', $entity->language()->getId());
    $form = ContentImportForm::create($this->container)->buildForm([], new FormState());
    $this->assertSame('all', $form['langcode']['#default_value']);
    $this->assertArrayHasKey('fr', $form['langcode']['#options']);
    $this->assertSame('All languages (import all translations)', (string) $form['langcode']['#options']['all']);
  }

  /**
   * ZIP batch operations preserve the selected language across requests.
   */
  public function testZipBatchLanguage(): void {
    $content = $this->getTestNodeData($this->container->get('uuid')->generate(), 'ZIP import', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $path = $this->container->get('file_system')->getTempDirectory() . '/multilingual-content.zip';
    $zip = new \ZipArchive();
    $this->assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
    $zip->addFromString('content.yml', Yaml::encode($content));
    $zip->close();
    $this->getImporter()->importFromZip($path, 'fr');
    $batch = batch_get();
    $operations = $batch['sets'][0]['operations'];
    $this->assertSame('fr', $operations[0][1][1]);
    $context = [];
    foreach ($operations as [$callback, $arguments]) {
      $arguments[] = &$context;
      call_user_func_array($callback, $arguments);
    }
    $this->assertSame('fr', $context['results'][0]->language()->getId());
    unlink($path);
  }

  /**
   * File and batch imports retain the explicitly selected language.
   */
  public function testFileAndBatchLanguage(): void {
    $content = $this->getTestNodeData($this->container->get('uuid')->generate(), 'File import', TRUE);
    $content['base_fields']['langcode'] = 'en';
    $path = $this->container->get('file_system')->getTempDirectory() . '/multilingual-content.yml';
    file_put_contents($path, Yaml::encode($content));
    $node = $this->getImporter()->importFromFile($path, 'fr');
    $this->assertSame('fr', $node->language()->getId());
    $context = [];
    ContentBatchImporter::batchImportFileInLanguage($path, 'es', $context);
    $this->assertSame('es', $context['results'][0]->language()->getId());
    $this->assertTrue($context['results'][0]->hasTranslation('fr'));
    unlink($path);
  }

}
