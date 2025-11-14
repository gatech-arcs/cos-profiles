<?php

declare(strict_types=1);

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Value object representing a grouping of active filters.
 */
final readonly class ActiveFilterGroup extends ActiveFilterBase {

  /**
   * Constructor.
   *
   * @param \Drupal\active_filters\ActiveFilter\ActiveFilter[] $activeFilters
   * @param mixed[] $configuration
   */
  public function __construct(
    MarkupInterface|string $label,
    string $name,
    private array $activeFilters,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ) {
    parent::__construct($label, $name, $configuration, $filter, $view);
  }

  /**
   * Get the group's active filter.
   *
   * @return \Drupal\active_filters\ActiveFilter\ActiveFilter[]
   */
  public function getActiveFilters(): array {
    return $this->activeFilters;
  }

}
