<?php

namespace Drupal\Tests\oembed_lazyload\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\oembed_lazyload\Routing\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Test cases pertaining to the route subscriber.
 *
 * @group oembed_lazyload
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * The subject under test.
   *
   * @var \Drupal\oembed_lazyload\Routing\RouteSubscriber
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->instance = new RouteSubscriber();
  }

  /**
   * Tests that the proper requirement is set on an appropriate route.
   */
  public function testAlterRoutes() {

    $route = $this->createMock(Route::class);

    $route_collection = $this->createMock(RouteCollection::class);

    $route_collection->method('get')
      ->with('media.oembed_iframe')
      ->willReturn($route);

    $event = $this->createMock(RouteBuildEvent::class);

    $event->method('getRouteCollection')
      ->willReturn($route_collection);

    $route
      ->expects(static::once())
      ->method('addRequirements')
      ->with(['_custom_access' => 'oembed_lazyload.iframe_access_checker:access']);

    $this->instance->onAlterRoutes($event);
  }

}
