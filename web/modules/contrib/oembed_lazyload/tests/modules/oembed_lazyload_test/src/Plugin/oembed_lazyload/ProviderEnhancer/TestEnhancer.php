<?php

namespace Drupal\oembed_lazyload_test\Plugin\oembed_lazyload\ProviderEnhancer;

use Drupal\oembed_lazyload\ProviderEnhancerBase;

/**
 * A minimal plugin implementation that does nothing.
 *
 * @ProviderEnhancer(
 *   id = "test",
 *   label = @Translation("Test"),
 *   providers = {
 *     "FakeTestProviderThatDoesNotExist"
 *   }
 * )
 */
class TestEnhancer extends ProviderEnhancerBase {

}
