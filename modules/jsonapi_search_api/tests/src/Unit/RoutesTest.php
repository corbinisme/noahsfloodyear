<?php declare(strict_types = 1);

namespace Drupal\Tests\jsonapi_search_api\Unit;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Drupal\jsonapi_search_api\Resource\IndexResource;
use Drupal\jsonapi_search_api\Routing\Routes;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Entity\SearchApiConfigEntityStorage;
use Drupal\search_api\IndexInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophecy\ProphecyInterface;

/**
 * Tests route generation.
 *
 * @group jsonapi_search_api
 * @coversDefaultClass \Drupal\jsonapi_search_api\Routing\Routes
 */
final class RoutesTest extends UnitTestCase {

  /**
   * @covers ::routes
   * @dataProvider routeDataProvider
   *
   * @param \Drupal\search_api\IndexInterface $mocked_index
   *   The mocked index.
   * @param bool $expect_disabled_index
   *   Boolean to check if the index is expected to be disabled.
   * @param string[] $expected_entity_types
   *   The expected entity types from the index darasources.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $mocked_resource_types
   *   The mocked resource types.
   */
  public function testRoutes(IndexInterface $mocked_index, bool $expect_disabled_index, array $expected_entity_types, array $mocked_resource_types) {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    foreach ($expected_entity_types as $expected_entity_type) {
      $entity_type_manager->hasDefinition($expected_entity_type)->willReturn(TRUE);
    }

    $index_storage = $this->prophesize(SearchApiConfigEntityStorage::class);
    $index_storage->loadMultiple()->willReturn([$mocked_index]);
    $entity_type_manager->getStorage('search_api_index')->willReturn($index_storage->reveal());

    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);
    $index_resource_type = $this->prophesize(ResourceType::class);
    $index_resource_type->getTypeName()->willReturn('search_api_index--search_api_index');
    $resource_type_repository->get('search_api_index', 'search_api_index')->willReturn($index_resource_type->reveal());
    foreach ($mocked_resource_types as $mocked_resource_type) {
      $resource_type_repository->get(
        $mocked_resource_type->getEntityTypeId(),
        $mocked_resource_type->getBundle()
      )->willReturn($mocked_resource_type);
    }

    $routes = new Routes(
      $entity_type_manager->reveal(),
      $resource_type_repository->reveal(),
      [],
      '/jsonapi'
    );
    $route_collection = $routes->routes();

    $sut = $route_collection->get('jsonapi_search_api.index_' . $mocked_index->id());

    if ($expect_disabled_index) {
      $this->assertFalse($mocked_index->status());
      $this->assertNull($sut);
    }
    else {
      $this->assertNotNull($sut);
      $this->assertEquals('/%jsonapi%/index/' . $mocked_index->id(), $sut->getPath());
      $this->assertEquals([
        'index' => ['type' => 'entity:search_api_index'],
      ], $sut->getOption('parameters'));
      $this->assertEquals(IndexResource::class, $sut->getDefault('_jsonapi_resource'));
      $this->assertEquals(array_map(static function (ResourceType $resource_type) {
        return $resource_type->getTypeName();
      }, $mocked_resource_types), $sut->getDefault('_jsonapi_resource_types'));
      $this->assertEquals($mocked_index->uuid(), $sut->getDefault('index'));
      $this->assertEquals('search_api_index--search_api_index', $sut->getDefault(JsonapiRoutes::RESOURCE_TYPE_KEY));
    }
  }

  /**
   * Test data for route generation.
   *
   * @return \Generator
   *   The test data.
   */
  public function routeDataProvider(): \Generator {
    $index = $this->getStubMockedIndex();
    $mocked_datasource = $this->prophesize(DatasourceInterface::class);
    $mocked_datasource->getEntityTypeId()->willReturn('node');
    $mocked_datasource->getBundles()->willReturn(['articles' => 'Articles']);
    $index->getDatasources()->willReturn([$mocked_datasource->reveal()]);

    yield [
      $index->reveal(),
      FALSE,
      ['node'],
      $this->getMockedResourceTypes('node', ['articles']),
    ];

    $index = $this->getStubMockedIndex('test_index', FALSE);
    $mocked_datasource = $this->prophesize(DatasourceInterface::class);
    $mocked_datasource->getEntityTypeId()->willReturn('node');
    $mocked_datasource->getBundles()->willReturn(['articles' => 'Articles']);
    $index->getDatasources()->willReturn([$mocked_datasource->reveal()]);
    yield [
      $index->reveal(),
      TRUE,
      ['node'],
      $this->getMockedResourceTypes('node', ['articles']),
    ];

    $index = $this->getStubMockedIndex();
    $mocked_datasource = $this->prophesize(DatasourceInterface::class);
    $mocked_datasource->getEntityTypeId()->willReturn('node');
    $mocked_datasource->getBundles()->willReturn([
      'articles' => 'Articles',
      'pages' => 'Pages',
    ]);
    $index->getDatasources()->willReturn([$mocked_datasource->reveal()]);

    yield [
      $index->reveal(),
      FALSE,
      ['node'],
      $this->getMockedResourceTypes('node', ['articles', 'pages']),
    ];
  }

  /**
   * Get an initial stubbed mocked index.
   *
   * @param string $index_id
   *   The index ID.
   * @param bool $status
   *   The index status.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The stub mocked index.
   */
  private function getStubMockedIndex(string $index_id = 'test_index', bool $status = TRUE): ProphecyInterface {
    $index = $this->prophesize(IndexInterface::class);
    $index->id()->willReturn($index_id);
    $index->uuid()->willReturn((new Php())->generate());
    $index->getEntityTypeId()->willReturn('search_api_index');
    $index->bundle()->willReturn('search_api_index');
    $index->status()->willReturn($status);
    return $index;
  }

  /**
   * Get mocked resource types.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $bundles
   *   The bundles.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The mocked resource types.
   */
  private function getMockedResourceTypes(string $entity_type_id, array $bundles): array {
    return array_map(function (string $bundle) use ($entity_type_id) {
      $mocked_resource_type = $this->prophesize(ResourceType::class);
      $mocked_resource_type->getEntityTypeId()->willReturn($entity_type_id);
      $mocked_resource_type->getBundle()->willReturn($bundle);
      $mocked_resource_type->getTypeName()->willReturn("{$entity_type_id}--{$bundle}");
      return $mocked_resource_type->reveal();
    }, $bundles);
  }

}
