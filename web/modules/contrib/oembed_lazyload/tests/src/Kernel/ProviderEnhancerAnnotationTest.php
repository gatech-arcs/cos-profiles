<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test cases pertaining to the provider enhancer annotation class.
 *
 * @coversClass \Drupal\oembed_lazyload\Annotation\ProviderEnhancer
 *
 * @group oembed_lazyload
 */
class ProviderEnhancerAnnotationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oembed_lazyload',
    'oembed_lazyload_test',
  ];

  /**
   * Test case for confirming annotation fields are working properly.
   */
  public function testAnnotations() {

    /** @var \Drupal\oembed_lazyload\ProviderEnhancerManagerInterface $manager */
    $manager = $this->container->get('oembed_lazyload');
    $plugin = $manager->getDefinition('test');
    static::assertEquals('test', $plugin['id']);
    static::assertEquals('Test', $plugin['label']);
    static::assertEquals(['FakeTestProviderThatDoesNotExist'], $plugin['providers']);
  }

}
