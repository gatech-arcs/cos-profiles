<?php

namespace Drupal\diff_plus\Theme;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Ensures that the default theme is used for raw HTML diffs.
 */
class RawHtmlThemeNegotiator implements ThemeNegotiatorInterface {

  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() === 'diff.revisions_diff' && $route_match->getParameter('filter') === 'raw_html';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->themeHandler->getDefault();
  }

}
