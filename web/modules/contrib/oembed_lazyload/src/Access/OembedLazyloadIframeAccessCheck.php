<?php

namespace Drupal\oembed_lazyload\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\oembed_lazyload\IframeUrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * An additional access check for loading oembed iframes.
 */
class OembedLazyloadIframeAccessCheck implements AccessInterface {

  /**
   * The oembed lazyload iFrame URL helper service.
   *
   * @var \Drupal\oembed_lazyload\IframeUrlHelper
   */
  protected $oembedLazyloadIframeUrlHelper;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * OembedLazyloadIframeAccessCheck constructor.
   *
   * @param \Drupal\oembed_lazyload\IframeUrlHelper $oembed_lazyload_iframe_url_helper
   *   The oembed lazyload iFrame URL helper service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(IframeUrlHelper $oembed_lazyload_iframe_url_helper, RequestStack $request_stack) {
    $this->oembedLazyloadIframeUrlHelper = $oembed_lazyload_iframe_url_helper;
    $this->requestStack = $request_stack;
  }

  /**
   * Ensures that the iframe url has not been tampered with.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $result = AccessResult::allowed();
    $query = $this->requestStack->getCurrentRequest()->query;
    if ($query && $query->has('oembed_lazyload')) {
      $user_string = $query->get('oembed_lazyload_hash');
      $provider = $query->get('provider');
      $third_party_settings = $query->all('options');
      $known_string = $this->oembedLazyloadIframeUrlHelper->getHash($provider, $third_party_settings);

      if (!hash_equals($known_string, $user_string)) {
        $result = AccessResult::forbidden();
      }
      $result->addCacheContexts([
        'url.query_args:oembed_lazyload_hash',
        'url.query_args:provider',
        'url.query_args:options',
      ]);
    }
    $result->addCacheContexts([
      'url.query_args:oembed_lazyload',
    ]);
    return $result;
  }

}
