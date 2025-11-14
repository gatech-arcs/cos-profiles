<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_lazyload\Plugin\oembed_lazyload\ProviderEnhancer\FallbackEnhancer;
use Drupal\oembed_lazyload_test\Plugin\oembed_lazyload\ProviderEnhancer\TestEnhancer;

/**
 * Test cases pertaining to the provider enhancer plugin manager.
 *
 * @coversClass \Drupal\oembed_lazyload\ProviderEnhancerManager
 *
 * @group oembed_lazyload
 */
class ProviderEnhancerManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oembed_lazyload',
    'oembed_lazyload_test',
  ];

  /**
   * The subject under test.
   *
   * @var \Drupal\oembed_lazyload\ProviderEnhancerManagerInterface
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->instance = $this->container->get('oembed_lazyload');
  }

  /**
   * Test case for the fallback plugin id.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerManager::getFallbackPluginId
   */
  public function testGetFallbackPluginId() {
    static::assertSame('fallback', $this->instance->getFallbackPluginId('non-existent'));
  }

  /**
   * Test case for retrieving an enhancer for a provider.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerManager::getEnhancerForProvider
   */
  public function testGetEnhancerForProvider() {
    // Make sure the correct fallback plugin is loaded.
    static::assertSame(FallbackEnhancer::class, get_class($this->instance->getEnhancerForProvider('non-existent')));

    // Make sure that the test plugin is loaded.
    static::assertSame(TestEnhancer::class, get_class($this->instance->getEnhancerForProvider('test')));
  }

}
