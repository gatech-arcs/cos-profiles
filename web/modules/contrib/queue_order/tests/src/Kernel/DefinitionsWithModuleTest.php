<?php

namespace Drupal\Tests\queue_order\Kernel;

/**
 * Class DefinitionsWithModuleTest.
 *
 * Test module functionality.
 *
 * @package Drupal\queue_order\Tests\Kernel
 *
 * @group queue_order
 */
class DefinitionsWithModuleTest extends DefinitionsWithoutModuleTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['queue_order_definition_fixtures', 'queue_order'];

  /**
   * Test equality of Queue Worker definition order.
   */
  public function testOrder() {
    $this->assertEquals(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is managed by the module'
    );
    $this->assertSame(
      $this->orderedList,
      array_keys($this->queueWorkerManager->getDefinitions()),
      'Order is managed by the module'
    );
  }

  /**
   * Test is functionality force creation of `cron` key.
   */
  public function testCronKeyExistence() {
    $definition = $this->queueWorkerManager
      ->getDefinition('queue_order_worker_B');
    $this->assertArrayNotHasKey('cron', $definition);
  }

}
