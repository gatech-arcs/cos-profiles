<?php

namespace Drupal\Tests\diff_plus\Unit;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\diff_plus\Theme\RawHtmlThemeNegotiator;
use Drupal\Tests\UnitTestCase;

/**
 * Contains test cases for the raw html theme negotiator.
 *
 * @group diff_plus
 */
class RawHtmlThemeNegotiatorTest extends UnitTestCase {

  /**
   * The subject under test.
   *
   * @var \Drupal\diff_plus\Theme\RawHtmlThemeNegotiator
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $theme_handler = $this->createMock(ThemeHandlerInterface::class);
    $theme_handler->method('getDefault')->willReturn('olivero');
    $this->instance = new RawHtmlThemeNegotiator($theme_handler);
  }

  /**
   * Test case for the ::applies method with a non-diff route.
   */
  public function testAppliesNonDiffRoute() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn('entity.node.canonical');
    static::assertFalse($this->instance->applies($route_match));
  }

  /**
   * Test case for the ::applies method with a non-raw-html filter.
   */
  public function testAppliesDiffRouteDifferentFilter() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn('diff.revisions_diff');
    $route_match->method('getParameter')->willReturn('visual');
    static::assertFalse($this->instance->applies($route_match));
  }

  /**
   * Test case for the ::applies method with a raw-html filter.
   */
  public function testAppliesDiffRouteRawHtmlFilter() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn('diff.revisions_diff');
    $route_match->method('getParameter')->willReturn('raw_html');
    static::assertTrue($this->instance->applies($route_match));
  }

  /**
   * Test case for the ::determineActiveTheme method.
   */
  public function testDetermineActiveTheme() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    static::assertEquals('olivero', $this->instance->determineActiveTheme($route_match));
  }

}
