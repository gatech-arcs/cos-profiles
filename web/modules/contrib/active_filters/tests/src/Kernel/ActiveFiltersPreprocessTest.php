<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Kernel;

use Drupal\active_filters\ActiveFilter\ActiveFilterFactoryInterface;
use Drupal\Core\Template\Attribute;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test preprocess data manipulations.
 */
#[Group('active_filters')]
#[CoversFunction('template_preprocess_active_filters')]
#[CoversFunction('template_preprocess_active_filters_grouped')]
#[CoversFunction('template_preprocess_active_filter')]
#[CoversFunction('template_preprocess_active_filter_group')]
final class ActiveFiltersPreprocessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'active_filters',
    'system',
    'views',
  ];

  private readonly ActiveFilterFactoryInterface $factory;

  private readonly FilterPluginBase $filter;

  private readonly ViewExecutable $view;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->factory = $this->container->get('active_filters.factory');
    $this->filter = $this->createMock(FilterPluginBase::class);
    $this->filter->options = [
      'expose' => ['label' => 'Exposed'],
      'group_info' => ['label' => 'Exposed Group'],
    ];
    $this->view = $this->createMock(ViewExecutable::class);
  }

  /**
   * Test variable manipulations for active filters templates.
   */
  public function testPreprocessActiveFilters(): void {
    $original_variables = $variables = [
      'configuration' => [
        'clear_text' => 'Test Clear',
        'hide_title' => TRUE,
      ],
      'content_attributes' => NULL,
      'title_prefix' => NULL,
      'title_suffix' => NULL,
      'active_filters' => [],
      'attributes' => [],
      'title' => '',
      'title_attributes' => [],
      'clear_text' => '',
      'clear_attributes' => [],
      'view' => $this->view,
    ];

    template_preprocess_active_filters($variables);
    $attributes = $variables['attributes'];
    self::assertInstanceOf(Attribute::class, $attributes);
    self::assertTrue($attributes->hasAttribute('data-active-filters'));
    self::assertTrue($attributes->hasAttribute('data-active-filters-clearable'));
    self::assertInstanceOf(Attribute::class, $variables['title_attributes']);
    self::assertTrue($variables['title_attributes']->hasClass('visually-hidden'));
    self::assertInstanceOf(Attribute::class, $variables['clear_attributes']);
    self::assertTrue($variables['clear_attributes']->hasAttribute('data-active-filters-clear-all'));
    self::assertArrayNotHasKey('content_attributes', $variables);
    self::assertArrayNotHasKey('title_prefix', $variables);
    self::assertArrayNotHasKey('title_suffix', $variables);

    $original_variables['configuration']['clear_text'] = '';
    $variables = $original_variables;
    template_preprocess_active_filters($variables);
    self::assertInstanceOf(Attribute::class, $variables['attributes']);
    self::assertFalse($variables['attributes']->hasAttribute('data-active-filters-clearable'));

    $original_variables['configuration']['hide_title'] = FALSE;
    $variables = $original_variables;
    template_preprocess_active_filters($variables);
    self::assertInstanceOf(Attribute::class, $variables['title_attributes']);
    self::assertFalse($variables['title_attributes']->hasClass('visually-hidden'));
  }

  /**
   * Test variable manipulations for grouped active filters templates.
   */
  public function testPreprocessActiveFiltersGrouped(): void {
    $variables = [
      'configuration' => [
        'clear_text' => 'Test Clear',
        'hide_title' => FALSE,
      ],
      'active_filters' => [],
      'attributes' => [],
      'title' => '',
      'title_attributes' => [],
      'clear_text' => '',
      'clear_attributes' => [],
      'view' => $this->view,
    ];
    template_preprocess_active_filters_grouped($variables);
    self::assertInstanceOf(Attribute::class, $variables['attributes']);
    self::assertTrue($variables['attributes']->hasAttribute('data-active-filters-grouped'));
  }

  /**
   * Test variable manipulations for active filter templates.
   */
  public function testPreprocessActiveFilter(): void {
    $original = $variables = [
      'active_filter' => $this->factory->createActiveFilter(
        'Label',
        'name',
        'value',
        TRUE,
        [],
        $this->filter,
        $this->view,
      ),
      'label' => '',
      'attributes' => [],
      'wrapper_attributes' => [],
    ];

    template_preprocess_active_filter($variables);
    $attributes = $variables['attributes'];
    self::assertInstanceOf(Attribute::class, $attributes);
    self::assertSame($original['active_filter']->getName(), $attributes->offsetGet('data-active-filter-name')->value());
    self::assertSame($original['active_filter']->getValue(), $attributes->offsetGet('data-active-filter-value')->value());
    self::assertTrue($attributes->hasAttribute('data-active-filter-removable'));
    self::assertFalse($attributes->hasAttribute('disabled'));
    self::assertFalse($attributes->hasAttribute('aria-disabled'));
    self::assertStringContainsString($this->filter->options['expose']['label'], $attributes->offsetGet('aria-label')->value());
    self::assertStringContainsString('Label', $attributes->offsetGet('aria-label')->value());
    self::assertStringContainsString('Remove', $attributes->offsetGet('aria-label')->value());
    self::assertInstanceOf(Attribute::class, $variables['wrapper_attributes']);
    self::assertArrayNotHasKey('content_attributes', $variables);
    self::assertArrayNotHasKey('title_attributes', $variables);
    self::assertArrayNotHasKey('title_prefix', $variables);
    self::assertArrayNotHasKey('title_suffix', $variables);

    $this->filter->options['expose']['label'] = '';
    $variables = [
      'active_filter' => $this->factory->createActiveFilter(
        'Label',
        'name',
        'value',
        FALSE,
        [],
        $this->filter,
        $this->view,
      ),
      'label' => '',
      'attributes' => [],
      'wrapper_attributes' => [],
    ];
    template_preprocess_active_filter($variables);
    $attributes = $variables['attributes'];
    self::assertInstanceOf(Attribute::class, $attributes);
    self::assertFalse($attributes->hasAttribute('data-active-filter-removable'));
    self::assertTrue($attributes->hasAttribute('disabled'));
    self::assertTrue($attributes->hasAttribute('aria-disabled'));
    self::assertStringContainsString($this->filter->options['group_info']['label'], $attributes->offsetGet('aria-label')->value());
    self::assertStringContainsString('Label', $attributes->offsetGet('aria-label')->value());
    self::assertStringNotContainsString('Remove', $attributes->offsetGet('aria-label')->value());
  }

  /**
   * Test variable manipulations for active filter group templates.
   */
  public function testPreprocessActiveFilterGroup(): void {
    $variables = [
      'group' => $this->factory->createActiveFilterGroup(
        'Label',
        'name',
        [],
        [],
        $this->filter,
        $this->view,
      ),
      'label' => '',
      'attributes' => [],
      'wrapper_attributes' => [],
    ];
    template_preprocess_active_filter_group($variables);
    self::assertInstanceOf(Attribute::class, $variables['wrapper_attributes']);
    self::assertInstanceOf(Attribute::class, $variables['attributes']);
    self::assertSame('name', $variables['attributes']->offsetGet('data-active-filter-name')->value());
    self::assertArrayNotHasKey('content_attributes', $variables);
    self::assertArrayNotHasKey('title_attributes', $variables);
    self::assertArrayNotHasKey('title_prefix', $variables);
    self::assertArrayNotHasKey('title_suffix', $variables);
  }

}
