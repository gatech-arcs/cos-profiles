<?php

namespace Drupal\active_filters\ActiveFilter;

use Drupal\views\ViewExecutable;

/**
 * Defines a class which creates render arrays from active filter value objects.
 */
interface RenderArrayBuilderInterface {

  /**
   * Generate an active filters render array from active filter value objects.
   *
   * @param \Drupal\active_filters\ActiveFilter\ActiveFilterBase[] $items
   * @param mixed[] $configuration
   *
   * @return array<string, mixed>
   */
  public function activeFilters(
    array $items,
    array $configuration,
    ViewExecutable $view,
  ): array;

  /**
   * Generate a render array for a single active filter value object.
   *
   * @return array<string, mixed>
   */
  public function activeFilter(ActiveFilterBase $item): array;

}
