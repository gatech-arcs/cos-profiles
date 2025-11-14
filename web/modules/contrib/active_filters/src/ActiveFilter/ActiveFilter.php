<?php

declare(strict_types=1);

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Value object representing a single active filter.
 */
final readonly class ActiveFilter extends ActiveFilterBase {

  /**
   * Constructor.
   *
   * @param mixed[] $configuration
   */
  public function __construct(
    MarkupInterface|string $label,
    string $name,
    private string $value,
    private bool $removable,
    array $configuration,
    FilterPluginBase $filter,
    ViewExecutable $view,
  ) {
    parent::__construct($label, $name, $configuration, $filter, $view);
  }

  /**
   * Get the value.
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * Get if the active filter is removable.
   */
  public function isRemovable(): bool {
    return $this->removable;
  }

}
