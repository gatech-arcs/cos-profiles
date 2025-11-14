<?php

namespace Drupal\diff_plus\Theme;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Ensures that the default theme is used for raw HTML diffs.
 */
class VisualInlineHtml5ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Creates a theme negotiator instance.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   */
  public function __construct(
    protected ThemeHandlerInterface $themeHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() === 'diff.revisions_diff' && $route_match->getParameter('filter') === 'visual_inline_html5';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->themeHandler->getDefault();
  }

}
