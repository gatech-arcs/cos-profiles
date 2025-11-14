<?php

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a factory which creates active filter value objects.
 */
interface ActiveFilterFactoryInterface {

  /**
   * Create an active filter value object.
   *
   * @param mixed[] $configuration
   */
  public function createActiveFilter(
    MarkupInterface|string $label,
    string $name,
    string $value,
    bool $removable,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ): ActiveFilter;

  /**
   * Create an active filter group value object.
   *
   * @param \Drupal\active_filters\ActiveFilter\ActiveFilter[] $active_filters
   * @param mixed[] $configuration
   */
  public function createActiveFilterGroup(
    MarkupInterface|string $label,
    string $name,
    array $active_filters,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ): ActiveFilterGroup;

}
