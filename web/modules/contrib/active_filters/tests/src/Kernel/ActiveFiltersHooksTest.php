<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Kernel;

use Drupal\active_filters\ActiveFilter\ActiveFilterFactoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests hooks implemented by Active Filters.
 */
#[Group('active_filters')]
#[CoversFunction('active_filters_theme_suggestions_alter')]
final class ActiveFiltersHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'active_filters',
    'system',
    'views',
  ];

  private readonly ActiveFilterFactoryInterface $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->factory = $this->container->get('active_filters.factory');
  }

  /**
   * Test the active filters theme suggestions alter hook.
   */
  public function testActiveFiltersThemeSuggestions(): void {
    $view = $this->createMock(ViewExecutable::class);
    $view->expects($this->any())
      ->method('id')
      ->willReturn('id');
    $view->current_display = 'display';
    $variables['view'] = $view;

    // Test active filters theme suggestions.
    $suggestions = [];
    active_filters_theme_suggestions_alter($suggestions, $variables, 'active_filters');
    self::assertSame([
      'active_filters__id',
      'active_filters__id__display',
    ], $suggestions);

    // Test grouped active filters theme suggestions.
    $suggestions = [];
    active_filters_theme_suggestions_alter($suggestions, $variables, 'active_filters_grouped');
    self::assertSame([
      'active_filters_grouped__id',
      'active_filters_grouped__id__display',
    ], $suggestions);

    // Test with an active filter.
    $variables['attributes']['data-active-filter-name'] = 'name';

    // Test active filter group theme suggestions.
    $variables['group'] = $this->factory->createActiveFilterGroup(
      'label',
      'name',
      [],
      [],
      $this->createMock(FilterPluginBase::class),
      $view,
    );
    $suggestions = [];
    active_filters_theme_suggestions_alter($suggestions, $variables, 'active_filter_group');
    self::assertSame([
      'active_filter_group__id',
      'active_filter_group__id__display',
      'active_filter_group__name',
      'active_filter_group__id__name',
      'active_filter_group__id__display__name',
    ], $suggestions);

    // Test active filter theme suggestions.
    $variables['active_filter'] = $this->factory->createActiveFilter(
      'label',
      'name',
      'value',
      TRUE,
      [],
      $this->createMock(FilterPluginBase::class),
      $view,
    );
    $suggestions = [];
    active_filters_theme_suggestions_alter($suggestions, $variables, 'active_filter');
    self::assertSame([
      'active_filter__id',
      'active_filter__id__display',
      'active_filter__name',
      'active_filter__name__value',
      'active_filter__id__name',
      'active_filter__id__display__name',
      'active_filter__id__name__value',
      'active_filter__id__display__name__value',
    ], $suggestions);
  }

}
