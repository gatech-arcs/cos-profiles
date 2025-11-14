<?php

namespace Drupal\oembed_lazyload;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\oembed_lazyload\Annotation\ProviderEnhancer;
use function in_array;

/**
 * Plugin manager implementation for provider enhancers.
 */
class ProviderEnhancerManager extends DefaultPluginManager implements ProviderEnhancerManagerInterface {

  /**
   * Constructs a new OEmbedLazyload manager instance.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/oembed_lazyload/ProviderEnhancer', $namespaces, $module_handler, ProviderEnhancerInterface::class, ProviderEnhancer::class);
    $this->alterInfo('oembed_lazyload');
    $this->setCacheBackend($cache_backend, 'oembed_lazyload');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'fallback';
  }

  /**
   * {@inheritdoc}
   */
  public function getEnhancerForProvider($provider) {
    $plugin_id = $provider;
    foreach ($this->getDefinitions() as $definition) {
      if (isset($definition['providers'])) {
        $providers = $definition['providers'];
        if (in_array($provider, $providers, TRUE)) {
          $plugin_id = $definition['id'];
          break;
        }
      }
    }
    return $this->createInstance($plugin_id);
  }

}
