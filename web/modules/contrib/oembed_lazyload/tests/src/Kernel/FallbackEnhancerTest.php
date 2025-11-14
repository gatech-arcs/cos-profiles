<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_lazyload\Plugin\oembed_lazyload\ProviderEnhancer\FallbackEnhancer;

/**
 * Test cases pertaining to the fallback enhancer plugin.
 *
 * @coversClass \Drupal\oembed_lazyload\Plugin\oembed_lazyload\ProviderEnhancer\FallbackEnhancer
 *
 * @group oembed_lazyload
 */
class FallbackEnhancerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oembed_lazyload',
  ];

  /**
   * Test case for confirming annotation fields are working properly.
   */
  public function testFallbackEnhancer() {
    /** @var \Drupal\oembed_lazyload\ProviderEnhancerManagerInterface $manager */
    $manager = $this->container->get('oembed_lazyload');
    $plugin = $manager->createInstance('does_not_exist');

    static::assertEquals(FallbackEnhancer::class, get_class($plugin));
  }

}
