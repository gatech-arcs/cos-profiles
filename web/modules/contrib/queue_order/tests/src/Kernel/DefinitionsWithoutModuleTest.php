<?php

namespace Drupal\Tests\queue_order\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class DefinitionsWithoutModuleTest.
 *
 * Tests compatibility with Drupal functionality.
 *
 * @package Drupal\queue_order\Tests\Kernel
 *
 * @group queue_order
 */
class DefinitionsWithoutModuleTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['queue_order_definition_fixtures'];

  /**
   * Queue Worker Manager service.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueWorkerManager;

  /**
   * Expected order for tests.
   *
   * @var string[]
   */
  protected $orderedList = [
    'queue_order_worker_B',
    'queue_order_worker_A',
    'queue_order_worker_D',
    'queue_order_worker_E',
    'queue_order_worker_C',
    'queue_order_worker_F',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->queueWorkerManager = \Drupal::service('plugin.manager.queue_worker');
  }

  /**
   * Test equality of Queue Worker definition order.
   */
  public function testOrder() {
    $this->assertNotEquals(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is managed by the core functionality'
    );
    $this->assertNotSame(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is managed by the core functionality'
    );
  }

}
