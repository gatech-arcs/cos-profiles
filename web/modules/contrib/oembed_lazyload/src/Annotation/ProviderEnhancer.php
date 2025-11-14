<?php

namespace Drupal\oembed_lazyload\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Provider Enhancer annotation.
 *
 * @Annotation
 */
class ProviderEnhancer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the enhancer plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The provider types that this enhancer applies to.
   *
   * @var string[]
   */
  public $providers = [];

}
