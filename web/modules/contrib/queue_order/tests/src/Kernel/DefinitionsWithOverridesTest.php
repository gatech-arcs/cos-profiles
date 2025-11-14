<?php

namespace Drupal\Tests\queue_order\Kernel;

/**
 * Class DefinitionsWithModuleTest.
 *
 * @package Drupal\queue_order\Tests\Kernel
 *
 * @group queue_order
 */
class DefinitionsWithOverridesTest extends DefinitionsWithoutModuleTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'queue_order_overrides_fixtures',
    'queue_order',
  ];

  /**
   * Expected order list.
   *
   * @var string[]
   * @see queue_order_overrides_fixtures/config/install/queue_order.settings.yml
   */
  protected $orderedList = [
    'queue_order_worker_F',
    'queue_order_worker_E',
    'queue_order_worker_D',
    'queue_order_worker_C',
    'queue_order_worker_B',
    'queue_order_worker_A',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['queue_order_overrides_fixtures']);
    $this->queueWorkerManager = \Drupal::service('plugin.manager.queue_worker');
  }

  /**
   * Test equality of Queue Worker definition order.
   */
  public function testOrder() {
    $this->assertNotEmpty(\Drupal::configFactory()->get('queue_order.settings')->get());
    $this->assertEquals(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is not managed by the config settings'
    );
    $this->assertSame(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is not managed by the config settings'
    );
  }

}
