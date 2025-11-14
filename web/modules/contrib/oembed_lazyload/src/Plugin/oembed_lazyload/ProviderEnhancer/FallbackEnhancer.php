<?php

namespace Drupal\oembed_lazyload\Plugin\oembed_lazyload\ProviderEnhancer;

use Drupal\oembed_lazyload\ProviderEnhancerBase;

/**
 * A fallback enhancer used when a provider is not directly supported.
 *
 * @ProviderEnhancer(
 *   id = "fallback",
 *   weight = 0,
 *   label = "Fallback enhancer",
 *   providers = { }
 * )
 */
class FallbackEnhancer extends ProviderEnhancerBase {

}
