<?php

namespace Drupal\oembed_lazyload;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Url;
use Drupal\media\OEmbed\Resource;

/**
 * The main interface for provider enhancer plugin types.
 *
 * Provider enhancers act as a means to facilitate enriched user experiences
 * when interacting with oembed resources coming from a particular provider.
 * For example, the most provider enhancers are capable of pulling in a
 * web-optimized up to date thumbnail in ways that the core OEmbed system is
 * not yet able to do.
 *
 * @todo Keep an eye on the Resource class!  It's internal!
 */
interface ProviderEnhancerInterface extends PluginInspectionInterface {

  /**
   * Gets an array of libraries to attach for this provider.
   *
   * @return array|string[]
   *   An array of libraries to attach for this provider.
   */
  public function getLibraries();

  /**
   * Gets a build array that forms the placeholder for an oembed video.
   *
   * @param string $url
   *   The url that the oembed resource resides at.
   * @param \Drupal\media\OEmbed\Resource $resource
   *   The resource to build a thumbnail for.
   * @param array $settings
   *   The settings of the formatter that this enhancer is running on.
   *
   * @return array
   *   A build array.
   */
  public function getPlaceholder($url, Resource $resource, array $settings);

  /**
   * Gets a build array that forms the iframe for an oembed video.
   *
   * @param \Drupal\Core\Url $url
   *   The iframe url.
   * @param \Drupal\media\Oembed\Resource $resource
   *   The resource to build an iframe for.
   * @param array $settings
   *   The formatter settings to build an iframe for.
   *
   * @return array
   *   A build array that forms the iframe for an oembed video.
   */
  public function getIframe(Url $url, Resource $resource, array $settings);

  /**
   * Alters the provided markup.
   *
   * @param string $markup
   *   The markup to alter.
   * @param array $options
   *   An associative array of additional options.
   *
   * @return string
   *   The altered markup.
   */
  public function alterOembedResponse($markup, array $options = []);

}
