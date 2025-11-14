<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Traits;

use Drupal\Core\Config\Config;

/**
 * Common properties and methods for Active Filters functional tests.
 */
trait ActiveFiltersFunctionalTestsTrait {

  /**
   * Paths and query options to load test views.
   *
   * @var array<string, array{path: string, options: array<string, array<string, int|string|string[]>>}>
   */
  private array $views = [
    'test' => [
      'path' => 'active-filters/test',
      'options' => [
        'query' => [
          'title' => 'Title',
          'field_boolean_value' => 2,
          'field_list_text_value' => ['a', 'b'],
          'field_taxonomy_target_id' => 1,
        ],
      ],
    ],
    'ajax' => [
      'path' => 'active-filters/test-ajax',
      'options' => [
        'query' => [
          'title' => 'Title',
          'field_boolean_value' => 2,
          'field_list_text_value' => ['a', 'b'],
          'field_taxonomy_target_id' => 1,
        ],
      ],
    ],
  ];

  /**
   * Load a test view with test query parameters.
   */
  private function loadView(string $type, bool $with_values = TRUE): string {
    return $this->drupalGet(
      $this->views[$type]['path'],
      $with_values ? $this->views[$type]['options'] : [],
    );
  }

  /**
   * Get the selector of an active filter.
   */
  private function selector(string $name, string $value): string {
    return "[data-active-filter-name='$name'][data-active-filter-value='$value']";
  }

  /**
   * Get the config of the active filters test view.
   */
  private function getViewConfig(): Config {
    return \Drupal::configFactory()->getEditable('views.view.active_filters_test');
  }

  /**
   * Update the configuration of the active filters test view.
   */
  private function updateViewConfig(string $key, mixed $value): void {
    $this->getViewConfig()->set($key, $value)->save();
  }

  /**
   * Update the active filter configuration of the active filters test view.
   */
  private function updateActiveFilterConfig(
    string $key,
    mixed $value,
    string $display_id = 'default',
  ): void {
    $this->updateViewConfig("display.$display_id.display_options.header.active_filters.$key", $value);
  }

}
