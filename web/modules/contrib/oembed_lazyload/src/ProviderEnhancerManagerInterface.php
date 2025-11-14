<?php

namespace Drupal\oembed_lazyload;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Contains additions to the default plugin manager interface.
 */
interface ProviderEnhancerManagerInterface extends PluginManagerInterface, FallbackPluginManagerInterface {

  /**
   * Gets a enhancer plugin for the provider.
   *
   * @param string $provider
   *   The provider to get an enhancer for.
   *
   * @return \Drupal\oembed_lazyload\ProviderEnhancerInterface
   *   The enhancer that applies to the provider.
   */
  public function getEnhancerForProvider($provider);

}
