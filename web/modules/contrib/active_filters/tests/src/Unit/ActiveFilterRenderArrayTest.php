<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Unit;

use Drupal\active_filters\ActiveFilter\RenderArrayBuilder;
use Drupal\active_filters\ActiveFilter\RenderArrayBuilderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests active filter value render array generation.
 */
#[Group('active_filters')]
#[CoversClass(RenderArrayBuilder::class)]
final class ActiveFilterRenderArrayTest extends ActiveFilterUnitTestBase {

  private readonly RenderArrayBuilderInterface $render;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->render = new RenderArrayBuilder();
  }

  /**
   * Test active filter render array generation.
   */
  public function testActiveFilter(): void {
    $active_filter = $this->render->activeFilter($this->activeFilter);
    self::assertSame('active_filter', $active_filter['#theme']);
    self::assertSame($this->label, $active_filter['#label']);
    self::assertSame($this->activeFilter, $active_filter['#active_filter']);
    self::assertSame(['library' => ['active_filters/js']], $active_filter['#attached']);
    $active_filter = $this->factory->createActiveFilter(
      $this->label,
      $this->name,
      $this->value,
      FALSE,
      $this->configuration,
      $this->filter,
      $this->view,
    );
    $active_filter = $this->render->activeFilter($active_filter);
    self::assertArrayNotHasKey('#attached', $active_filter);

    $group = $this->render->activeFilter($this->group);
    self::assertSame('active_filter_group', ((array) $group['label'])['#theme'] ?? '');
    self::assertSame($this->label, ((array) $group['label'])['#label'] ?? '');
    self::assertSame($this->group, ((array) $group['label'])['#group'] ?? '');
    self::assertCount(1, (array) $group['active_filters']);
    self::assertArrayHasKey(0, (array) $group['active_filters']);
    self::assertSame('active_filter', ((array) $group['active_filters'])[0]['#theme'] ?? '');
  }

  /**
   * Test active filters render array generation.
   */
  public function testActiveFilters(): void {
    $not_grouped = $this->render->activeFilters(
      $this->activeFilters,
      $this->configuration,
      $this->view,
    );
    self::assertSame('active_filters', $not_grouped['#theme']);
    self::assertSame($this->configuration['title'], $not_grouped['#title']);
    self::assertSame($this->configuration['clear_text'], $not_grouped['#clear_text']);
    self::assertCount(1, (array) $not_grouped['#active_filters']);
    self::assertArrayHasKey(0, (array) $not_grouped['#active_filters']);
    self::assertSame('active_filter', ((array) $not_grouped['#active_filters'])[0]['#theme'] ?? '');
    self::assertSame($this->configuration, $not_grouped['#configuration']);
    self::assertSame($this->view, $not_grouped['#view']);
    self::assertSame(['library' => ['active_filters/css']], $not_grouped['#attached']);

    $grouped = $this->render->activeFilters(
      [$this->group],
      ['grouped' => TRUE] + $this->configuration,
      $this->view,
    );
    self::assertSame('active_filters_grouped', $grouped['#theme']);
  }

}
