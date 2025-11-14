<?php

declare(strict_types=1);

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Creates active filter value objects.
 */
final readonly class ActiveFilterFactory implements ActiveFilterFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createActiveFilter(
    MarkupInterface|string $label,
    string $name,
    string $value,
    bool $removable,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ): ActiveFilter {
    return new ActiveFilter($label, $name, $value, $removable, $configuration, $filter, $view);
  }

  /**
   * {@inheritdoc}
   */
  public function createActiveFilterGroup(
    MarkupInterface|string $label,
    string $name,
    array $active_filters,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ): ActiveFilterGroup {
    return new ActiveFilterGroup($label, $name, $active_filters, $configuration, $filter, $view);
  }

}
