<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Unit;

use Drupal\active_filters\ActiveFilter\ActiveFilter;
use Drupal\active_filters\ActiveFilter\ActiveFilterFactory;
use Drupal\active_filters\ActiveFilter\ActiveFilterFactoryInterface;
use Drupal\active_filters\ActiveFilter\ActiveFilterGroup;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Base class for active filter unit tests which sets up test data.
 */
abstract class ActiveFilterUnitTestBase extends UnitTestCase {

  protected readonly ActiveFilterFactoryInterface $factory;

  protected readonly string $label;

  protected readonly string $name;

  protected readonly string $value;

  protected readonly bool $removable;

  /**
   * Active filters.
   *
   * @var \Drupal\active_filters\ActiveFilter\ActiveFilter[]
   */
  protected readonly array $activeFilters;

  /**
   * Configuration.
   *
   * @var mixed[]
   */
  protected readonly array $configuration;

  protected readonly FilterPluginBase $filter;

  protected readonly ViewExecutable $view;

  protected readonly ActiveFilter $activeFilter;

  protected readonly ActiveFilterGroup $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->factory = new ActiveFilterFactory();

    $this->label = 'Test Label';
    $this->name = 'test_name';
    $this->value = 'test_value';
    $this->removable = TRUE;
    $this->configuration = [
      'title' => 'Test Title',
      'clear_text' => 'Test Clear',
      'grouped' => FALSE,
    ];
    $this->filter = $this->createMock(FilterPluginBase::class);
    $this->view = $this->createMock(ViewExecutable::class);

    $this->activeFilter = $this->factory->createActiveFilter(
      $this->label,
      $this->name,
      $this->value,
      $this->removable,
      $this->configuration,
      $this->filter,
      $this->view,
    );
    $this->activeFilters = [$this->activeFilter];
    $this->group = $this->factory->createActiveFilterGroup(
      $this->label,
      $this->name,
      $this->activeFilters,
      $this->configuration,
      $this->filter,
      $this->view,
    );
  }

}
