<?php

namespace Drupal\oembed_lazyload\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route subscriber that sets an additional requirement on the core route.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('media.oembed_iframe')) {
      $route->addRequirements([
        '_custom_access' => 'oembed_lazyload.iframe_access_checker:access',
      ]);
    }
  }

}
