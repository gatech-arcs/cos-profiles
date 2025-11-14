<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Unit;

use Drupal\active_filters\ActiveFilter\ActiveFilter;
use Drupal\active_filters\ActiveFilter\ActiveFilterBase;
use Drupal\active_filters\ActiveFilter\ActiveFilterGroup;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests active filter value objects.
 */
#[Group('active_filters')]
#[CoversClass(ActiveFilterBase::class)]
#[CoversClass(ActiveFilter::class)]
#[CoversClass(ActiveFilterGroup::class)]
final class ActiveFilterValueObjectsTest extends ActiveFilterUnitTestBase {

  /**
   * Test getting the label.
   */
  public function testGetLabel(): void {
    self::assertSame($this->label, $this->activeFilter->getLabel());
    self::assertSame($this->label, $this->group->getLabel());
  }

  /**
   * Test getting the name.
   */
  public function testGetName(): void {
    self::assertSame($this->name, $this->activeFilter->getName());
    self::assertSame($this->name, $this->group->getName());
  }

  /**
   * Test getting the configuration.
   */
  public function testGetConfiguration(): void {
    self::assertSame($this->configuration, $this->activeFilter->getConfiguration());
    self::assertSame($this->configuration, $this->group->getConfiguration());
  }

  /**
   * Test getting the filter.
   */
  public function testGetFilter(): void {
    self::assertSame($this->filter, $this->activeFilter->getFilter());
    self::assertSame($this->filter, $this->group->getFilter());
  }

  /**
   * Test getting the view.
   */
  public function testGetView(): void {
    self::assertSame($this->view, $this->activeFilter->getView());
    self::assertSame($this->view, $this->group->getView());
  }

  /**
   * Test getting the value.
   */
  public function testGetValue(): void {
    self::assertSame($this->value, $this->activeFilter->getValue());
  }

  /**
   * Test checking if the active filter is removable.
   */
  public function testIsRemovable(): void {
    self::assertSame($this->removable, $this->activeFilter->isRemovable());
  }

  /**
   * Test getting the grouped active filters.
   */
  public function testGetActiveFilters(): void {
    self::assertSame($this->activeFilters, $this->group->getActiveFilters());
  }

}
