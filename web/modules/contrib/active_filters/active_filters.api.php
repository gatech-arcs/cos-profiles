<?php

/**
 * @file
 * Hooks for the Active Filters module.
 */

declare(strict_types=1);

/**
 * Alter active filters before rendering.
 *
 * @param \Drupal\active_filters\ActiveFilter\ActiveFilterBase[] &$active_filters
 *   Active filter and group label objects.
 * @param \Drupal\views\ViewExecutable $view
 *   The view displaying the active filters.
 */
function hook_active_filters_alter(
  array &$active_filters,
  \Drupal\views\ViewExecutable $view,
): void {
  if (
    $view->id() === 'my_view'
    && $view->current_display === 'my_display'
  ) {
    // Rewrite all topic active filter labels.
    foreach ($active_filters as $i => $active_filter) {
      if (
        $active_filter instanceof \Drupal\active_filters\ActiveFilter\ActiveFilter
        && $active_filter->getName() === 'topic'
      ) {
        $active_filters[$i] = \Drupal::service('active_filters.factory')->createActiveFilter(
          t('Topic: @topic', ['@topic' => (string) $active_filter->getLabel()]),
          $active_filter->getName(),
          $active_filter->getValue(),
          $active_filter->isRemovable(),
          $active_filter->getConfiguration(),
          $active_filter->getFilter(),
          $active_filter->getView(),
        );
      }
    }
  }
}
