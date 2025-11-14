<?php

declare(strict_types=1);

namespace Drupal\active_filters\ActiveFilter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Base class for active filter value objects.
 */
abstract readonly class ActiveFilterBase {

  /**
   * Constructor.
   *
   * @param mixed[] $configuration
   */
  public function __construct(
    private MarkupInterface|string $label,
    private string $name,
    private array $configuration,
    private FilterPluginBase $filter,
    private ViewExecutable $view,
  ) {}

  /**
   * Get the label.
   */
  public function getLabel(): MarkupInterface|string {
    return $this->label;
  }

  /**
   * Get the name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Get the active filter configuration.
   *
   * @return mixed[]
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * Get the exposed filter the active filter is generated from.
   */
  public function getFilter(): FilterPluginBase {
    return $this->filter;
  }

  /**
   * Get the view the active filter is generated from.
   */
  public function getView(): ViewExecutable {
    return $this->view;
  }

}
