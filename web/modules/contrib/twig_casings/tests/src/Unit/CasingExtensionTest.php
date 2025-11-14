<?php

namespace Drupal\Tests\twig_casings\Unit;

use Drupal\twig_casings\Twig\CasingExtension;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Twig Casings module.
 *
 * @group twig_casings
 */
class CasingExtensionTest extends TestCase {

  /**
   * The Twig Casing extension service.
   *
   * @var \Drupal\twig_casings\Twig\CasingExtension
   */
  protected $casingExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->casingExtension = new CasingExtension();
  }

  /**
   * Helper function to get a filter callable.
   */
  protected function getFilterCallable(string $filterName) {
    /** @var \Twig\TwigFilter[] $filters */
    $filters = $this->casingExtension->getFilters();
    foreach ($filters as $filter) {
      if ($filter->getName() === $filterName) {
        return $filter->getCallable();
      }
    }
    throw new \Exception("Filter {$filterName} not found.");
  }

  /**
   * Tests the |camel_case filter.
   */
  public function testCamelCase() {
    $callable = $this->getFilterCallable('camel_case');
    $this->assertEquals('someStringWithDashesInIt', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('someStringWithSpacesInIt', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('someStringWithUnderscoresInIt', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

  /**
   * Tests the |kebab_case filter.
   */
  public function testKebabCase() {
    $callable = $this->getFilterCallable('kebab_case');
    $this->assertEquals('some-string-with-dashes-in-it', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('some-string-with-spaces-in-it', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('some-string-with-underscores-in-it', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

  /**
   * Tests the |macro_case filter.
   */
  public function testMacroCase() {
    $callable = $this->getFilterCallable('macro_case');
    $this->assertEquals('SOME_STRING_WITH_DASHES_IN_IT', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('SOME_STRING_WITH_SPACES_IN_IT', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('SOME_STRING_WITH_UNDERSCORES_IN_IT', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

  /**
   * Tests the |pascal_case filter.
   */
  public function testPascalCase() {
    $callable = $this->getFilterCallable('pascal_case');
    $this->assertEquals('SomeStringWithDashesInIt', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('SomeStringWithSpacesInIt', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('SomeStringWithUnderscoresInIt', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

  /**
   * Tests the |snake_case filter.
   */
  public function testSnakeCase() {
    $callable = $this->getFilterCallable('snake_case');
    $this->assertEquals('some_string_with_dashes_in_it', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('some_string_with_spaces_in_it', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('some_string_with_underscores_in_it', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

  /**
   * Tests the |train_case filter.
   */
  public function testTrainCase() {
    $callable = $this->getFilterCallable('train_case');
    $this->assertEquals('Some-String-With-Dashes-In-It', call_user_func($callable, 'Some-string-with-dashes-in-it'));
    $this->assertEquals('Some-String-With-Spaces-In-It', call_user_func($callable, 'Some string with spaces in it'));
    $this->assertEquals('Some-String-With-Underscores-In-It', call_user_func($callable, 'Some_string_with_underscores_in_it'));
  }

}
