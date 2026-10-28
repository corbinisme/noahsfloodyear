<?php

namespace Drupal\Tests\single_content_sync\Functional;

use Drupal\Core\Archiver\Zip;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Public and private file export tests.
 */
final class PublicPrivateFilesImportContentTest extends BrowserTestBase {

  use FileFieldCreationTrait;

  private const PUBLIC_FILE_FIELD_NAME = 'field_public_file';

  private const PRIVATE_FILE_FIELD_NAME = 'field_private_file';

  private const PUBLIC_FILE = 'public://public.txt';

  private const PUBLIC_FILE_CONTENTS = 'This is a PUBLIC file.';

  private const PRIVATE_FILE = 'private://private.txt';

  private const PRIVATE_FILE_CONTENTS = 'This is a PRIVATE file.';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'single_content_sync',
    'node',
  ];

  /**
   * A Node that is used for the tests.
   */
  private NodeInterface $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Node type.
    $node_type = $this->drupalCreateContentType();

    // Add public and private file fields to node type.
    $bundle_of = $node_type->getEntityType()->getBundleOf();
    \assert(\is_string($bundle_of));
    $bundle_id = $node_type->id();
    \assert(\is_string($bundle_id));
    $this->createFileField(self::PUBLIC_FILE_FIELD_NAME, $bundle_of, $bundle_id);
    $this->createFileField(self::PRIVATE_FILE_FIELD_NAME, $bundle_of, $bundle_id);

    // Create node.
    $this->testNode = $this->drupalCreateNode([
      'type' => $node_type->id(),
    ]);

    // Create public and private Files.
    $public_file = \Drupal::service('file.repository')->writeData(self::PUBLIC_FILE_CONTENTS, self::PUBLIC_FILE);
    $private_file = \Drupal::service('file.repository')->writeData(self::PRIVATE_FILE_CONTENTS, self::PRIVATE_FILE);

    // Set pubic and private files on node.
    $this->testNode->set(self::PUBLIC_FILE_FIELD_NAME, $public_file->id());
    $this->testNode->set(self::PRIVATE_FILE_FIELD_NAME, $private_file->id());
    $this->testNode->save();
  }

  /**
   * Check that a custom File field can be exported to an array.
   */
  public function testExportCustomFileFieldValuesToArray(): void {
    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $public_file_field_values = $content_exporter->getFieldValue($this->testNode->get(self::PUBLIC_FILE_FIELD_NAME));
    $private_file_field_values = $content_exporter->getFieldValue($this->testNode->get(self::PRIVATE_FILE_FIELD_NAME));

    self::assertSame(self::PUBLIC_FILE, $public_file_field_values[0]['target_entity']['base_fields']['uri']);
    self::assertSame(self::PUBLIC_FILE_CONTENTS, $this->drupalGet($public_file_field_values[0]['target_entity']['base_fields']['url']));
    self::assertSame(self::PRIVATE_FILE, $private_file_field_values[0]['target_entity']['base_fields']['uri']);
    self::assertSame(self::PRIVATE_FILE_CONTENTS, $this->drupalGet($private_file_field_values[0]['target_entity']['base_fields']['url']));
  }

  /**
   * Check exported asset file's folder structure.
   *
   * When a file asset is exported it is sorted into separate folders according
   * to its scheme. Public files go into 'assets/public', private files go into
   * 'assets/private' folder. The importer knows about this separation and uses
   * the scheme from the 'assets/{scheme}' folder structure to decide whether
   * the file is private or public and to import it accordingly.
   *
   * Yes, the exporter and importer communicate via the folder structure. It is
   * not pretty, but it works.
   */
  public function testCopyFileAssetsOfFilesReferencedByCustomFileFieldsToExportDirectory(): void {
    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $content_file_generator = \Drupal::service('single_content_sync.file_generator');
    $zip_archiver = \Drupal::service('plugin.manager.archiver');

    // getFieldValue must be called so that it populates 'export.assets' temp
    // store.
    $content_exporter->getFieldValue($this->testNode->get(self::PUBLIC_FILE_FIELD_NAME));
    $content_exporter->getFieldValue($this->testNode->get(self::PRIVATE_FILE_FIELD_NAME));
    $zip_file = $content_file_generator->generateZipFile($this->testNode);
    $zip = $zip_archiver->getInstance([
      'filepath' => $zip_file->getFileUri(),
    ]);

    self::assertInstanceOf(Zip::class, $zip);
    self::assertContains('assets/public/public.txt', $zip->listContents());
    self::assertContains('assets/private/private.txt', $zip->listContents());
  }

  /**
   * Check full export/import cycle.
   */
  public function testImportFileEntitiesWithTheirAssets(): void {
    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $content_file_generator = \Drupal::service('single_content_sync.file_generator');
    $content_importer = \Drupal::service('single_content_sync.importer');

    // getFieldValue must be called so that it populates 'export.assets' temp
    // store.
    $content_exporter->getFieldValue($this->testNode->get(self::PUBLIC_FILE_FIELD_NAME));
    $content_exporter->getFieldValue($this->testNode->get(self::PRIVATE_FILE_FIELD_NAME));
    $zip_file = $content_file_generator->generateZipFile($this->testNode);

    // Remove content from site, so that they are not mixed in with the newly
    // created entities. Also since the UUID of testNode is the same it would
    // only be updated instead of recreated.
    $public_file = $this->testNode->get(self::PUBLIC_FILE_FIELD_NAME)->entity;
    \assert($public_file instanceof FileInterface);
    $private_file = $this->testNode->get(self::PRIVATE_FILE_FIELD_NAME)->entity;
    \assert($private_file instanceof FileInterface);
    $public_file->delete();
    $private_file->delete();
    $this->testNode->delete();

    // Do importing from zip file.
    $content_importer->importFromZip($zip_file->getFileUri());
    batch_process();
    _batch_process();

    // Check that the new node's files are public and private files.
    $nodes = Node::loadMultiple();
    // It is assumed that only one node exists at this point.
    $new_node = array_pop($nodes);
    \assert($new_node instanceof NodeInterface);
    $new_public_file = $new_node->get(self::PUBLIC_FILE_FIELD_NAME)->entity;
    \assert($new_public_file instanceof FileInterface);
    $new_private_file = $new_node->get(self::PRIVATE_FILE_FIELD_NAME)->entity;
    \assert($new_private_file instanceof FileInterface);
    self::assertSame(self::PUBLIC_FILE, $new_public_file->getFileUri());
    self::assertSame(self::PRIVATE_FILE, $new_private_file->getFileUri());
    self::assertSame(self::PUBLIC_FILE_CONTENTS, file_get_contents($new_public_file->getFileUri()));
    self::assertSame(self::PRIVATE_FILE_CONTENTS, file_get_contents($new_private_file->getFileUri()));
  }

  /**
   * Tests that generated files include entities exported in an earlier call.
   */
  public function testGeneratedFilesResetExportCaches(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_related_node',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_related_node',
      'entity_type' => 'node',
      'bundle' => $this->testNode->bundle(),
    ])->save();
    $related_node = $this->drupalCreateNode(['type' => $this->testNode->bundle()]);
    // Reload the node so it has the newly created field.
    $this->testNode = Node::load($this->testNode->id());
    $this->testNode->set('field_related_node', $related_node);
    $this->testNode->save();

    $content_exporter = \Drupal::service('single_content_sync.exporter');
    $content_file_generator = \Drupal::service('single_content_sync.file_generator');
    $content_exporter->doExportToArray($this->testNode);

    foreach (['yaml', 'zip', 'bulk_zip'] as $format) {
      if ($format === 'yaml') {
        $file = $content_file_generator->generateYamlFile($this->testNode);
        $content = Yaml::decode(file_get_contents($file->getFileUri()));
      }
      else {
        $file = $format === 'zip'
          ? $content_file_generator->generateZipFile($this->testNode)
          : $content_file_generator->generateBulkZipFile([$this->testNode], FALSE, TRUE);
        $zip = new \ZipArchive();
        self::assertTrue($zip->open(\Drupal::service('file_system')->realpath($file->getFileUri())));
        $file_name = \Drupal::service('single_content_sync.helper')->generateContentFileName($this->testNode);
        $content = Yaml::decode($zip->getFromName("{$file_name}.yml"));
        self::assertSame(self::PUBLIC_FILE_CONTENTS, $zip->getFromName('assets/public/public.txt'));
        self::assertSame(self::PRIVATE_FILE_CONTENTS, $zip->getFromName('assets/private/private.txt'));
        $zip->close();
      }

      self::assertArrayHasKey('custom_fields', $content);
      self::assertArrayHasKey('custom_fields', $content['custom_fields']['field_related_node'][0]);
      self::assertArrayHasKey('custom_fields', $content['custom_fields'][self::PUBLIC_FILE_FIELD_NAME][0]['target_entity']);
      self::assertArrayHasKey('custom_fields', $content['custom_fields'][self::PRIVATE_FILE_FIELD_NAME][0]['target_entity']);
    }
  }

}
