<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

/**
 * Verifies all tests pass with batching enabled, uneven batches.
 *
 * @group migrate
 */
final class MigrateTableBatchTest extends MigrateTableTest {

  /**
   * The batch size to configure (a size of 1 disables batching).
   *
   * @var int
   */
  protected $batchSize = 2;

}
