<?php

declare(strict_types=1);

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\views\ViewExecutable;

/**
 * Builds render arrays for active filter value objects.
 */
final readonly class RenderArrayBuilder implements RenderArrayBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function activeFilters(
    array $items,
    array $configuration,
    ViewExecutable $view,
  ): array {
    $render = [
      '#theme' => 'active_filters' . ($configuration['grouped'] ? '_grouped' : ''),
      '#title' => $configuration['title'],
      '#clear_text' => $configuration['clear_text'],
      '#active_filters' => array_map(
        fn(ActiveFilterBase $item) => $this->activeFilter($item),
        $items,
      ),
      '#configuration' => $configuration,
      '#view' => $view,
      '#attached' => [
        'library' => [
          'active_filters/css',
        ],
      ],
    ];

    CacheableMetadata::createFromObject($view)->applyTo($render);

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function activeFilter(ActiveFilterBase $item): array {
    return match ($item::class) {
      ActiveFilter::class => $this->fromActiveFilter($item),
      ActiveFilterGroup::class => $this->fromActiveFilterGroup($item),
      default => [],
    };
  }

  /**
   * Generate a render array from an active filter value object.
   *
   * @return mixed[]
   */
  private function fromActiveFilter(ActiveFilter $active_filter): array {
    $render = [
      '#theme' => 'active_filter',
      '#label' => $active_filter->getLabel(),
      '#active_filter' => $active_filter,
    ];

    if ($active_filter->isRemovable()) {
      $render['#attached']['library'][] = 'active_filters/js';
    }

    return $render;
  }

  /**
   * Generate a render array from an active filter group value object.
   *
   * @return mixed[]
   */
  private function fromActiveFilterGroup(ActiveFilterGroup $group): array {
    return [
      'label' => [
        '#theme' => 'active_filter_group',
        '#label' => $group->getLabel(),
        '#group' => $group,
      ],
      'active_filters' => array_map(
        fn(ActiveFilter $item) => $this->activeFilter($item),
        $group->getActiveFilters(),
      ),
    ];
  }

}
