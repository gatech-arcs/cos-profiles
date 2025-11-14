<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\Functional;

use Drupal\active_filters\Plugin\views\area\ActiveFilters;
use Drupal\Tests\active_filters\Traits\ActiveFiltersFunctionalTestsTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the generation of Active Filters.
 */
#[Group('active_filters')]
#[CoversClass(ActiveFilters::class)]
#[CoversFunction('active_filters_test_uninstall')]
final class ActiveFiltersGenerationTest extends BrowserTestBase {

  use ActiveFiltersFunctionalTestsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'active_filters',
    'active_filters_test',
    'node',
    'options',
    'taxonomy',
    'views',
  ];

  /**
   * Test active filter generation of text input exposed filters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testTextExposedFilter(): void {
    // Test the text filter exists, has the correct value, and is not disabled.
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementTextEquals(
      'css',
      $this->selector('title', 'Title'),
      'Title',
    );
    $session->elementAttributeNotExists(
      'css',
      $this->selector('title', 'Title'),
      'aria-disabled',
    );

    // Test that the text filter can be made non-removable.
    $this->updateActiveFilterConfig('filters.title.removable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementAttributeContains(
      'css',
      $this->selector('title', 'Title'),
      'aria-disabled',
      'true',
    );

    // Test the text filter rewriting.
    $this->updateActiveFilterConfig('filters.title.rewrite', "Title|rewrite\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementTextEquals(
      'css',
      $this->selector('title', 'Title'),
      'rewrite',
    );

    // Test that text filter rewriting to an empty value removes it from the
    // page.
    $this->updateActiveFilterConfig('filters.title.rewrite', "Title|\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('title', 'rewrite'));

    // Test that disabling the text filter removes it from the page.
    $this->updateActiveFilterConfig('filters.title.rewrite', '');
    $this->updateActiveFilterConfig('filters.title.enable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('title', 'Title'));
  }

  /**
   * Test active filter generation of radio boolean input exposed filters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRadioBooleanExposedFilter(): void {
    // Test the boolean filter exists, has the correct value, and is disabled.
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementTextEquals(
      'css',
      $this->selector('field_boolean_value', '2'),
      'False',
    );
    $session->elementAttributeContains(
      'css',
      $this->selector('field_boolean_value', '2'),
      'aria-disabled',
      'true',
    );

    // Test that boolean filters can be removable if they are optional.
    $this->updateViewConfig(
      'display.test.display_options.filters.field_boolean_value.group_info.optional',
      TRUE,
    );
    $this->loadView('test');
    $this->assertSession()->elementAttributeNotExists(
      'css',
      $this->selector('field_boolean_value', '2'),
      'aria-disabled',
    );

    // Test that the boolean filter can be made non-removable.
    $this->updateActiveFilterConfig('filters.field_boolean_value.removable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementAttributeContains(
      'css',
      $this->selector('field_boolean_value', '2'),
      'aria-disabled',
      'true',
    );

    // Test the boolean filter rewriting.
    $this->updateActiveFilterConfig('filters.field_boolean_value.rewrite', "False|rewrite\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementTextEquals(
      'css',
      $this->selector('field_boolean_value', '2'),
      'rewrite',
    );

    // Test that boolean filter rewriting to an empty value removes it from the
    // page.
    $this->updateActiveFilterConfig('filters.field_boolean_value.rewrite', "False|\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('field_boolean_value', '2'));

    // Test that disabling the boolean filter removes it from the page.
    $this->updateActiveFilterConfig('filters.field_boolean_value.rewrite', '');
    $this->updateActiveFilterConfig('filters.field_boolean_value.enable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('field_boolean_value', '2'));
  }

  /**
   * Test active filter generation of multiselect input exposed filters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testMultiselectExposedFilter(): void {
    // Test the multiselect filters exist, have the correct value, and are not
    // disabled.
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementTextEquals(
      'css',
      $this->selector('field_list_text_value', 'a'),
      'Alpha',
    );
    $session->elementAttributeNotExists(
      'css',
      $this->selector('field_list_text_value', 'a'),
      'aria-disabled',
    );
    $session->elementTextEquals(
      'css',
      $this->selector('field_list_text_value', 'b'),
      'Bravo',
    );
    $session->elementAttributeNotExists(
      'css',
      $this->selector('field_list_text_value', 'b'),
      'aria-disabled',
    );

    // Test that the multiselect filters can be made non-removable.
    $this->updateActiveFilterConfig('filters.field_list_text_value.removable', FALSE);
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementAttributeContains(
      'css',
      $this->selector('field_list_text_value', 'a'),
      'aria-disabled',
      'true',
    );
    $session->elementAttributeContains(
      'css',
      $this->selector('field_list_text_value', 'b'),
      'aria-disabled',
      'true',
    );

    // Test the multiselect filter rewriting.
    $this->updateActiveFilterConfig('filters.field_list_text_value.rewrite', "Alpha|rewrite\r\nnull|null");
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementTextEquals(
      'css',
      $this->selector('field_list_text_value', 'a'),
      'rewrite',
    );
    $session->elementTextEquals(
      'css',
      $this->selector('field_list_text_value', 'b'),
      'Bravo',
    );

    // Test that multiselect filter rewriting to an empty value removes it from
    // the page.
    $this->updateActiveFilterConfig('filters.field_list_text_value.rewrite', "Alpha|\r\nnull|null");
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementNotExists('css', $this->selector('field_list_text_value', 'a'));
    $session->elementTextEquals(
      'css',
      $this->selector('field_list_text_value', 'b'),
      'Bravo',
    );

    // Test that disabling the multiselect filter removes it from the page.
    $this->updateActiveFilterConfig('filters.field_list_text_value.rewrite', '');
    $this->updateActiveFilterConfig('filters.field_list_text_value.enable', FALSE);
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementNotExists('css', $this->selector('field_list_text_value', 'a'));
    $session->elementNotExists('css', $this->selector('field_list_text_value', 'b'));
  }

  /**
   * Test active filter generation of select exposed filters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSelectExposedFilter(): void {
    // Test the select filter exists, has the correct value, and is not
    // disabled.
    $this->loadView('test');
    $session = $this->assertSession();
    $session->elementTextEquals(
      'css',
      $this->selector('field_taxonomy_target_id', '1'),
      'Alpha',
    );
    $session->elementAttributeNotExists(
      'css',
      $this->selector('field_taxonomy_target_id', '1'),
      'aria-disabled',
    );

    // Test that the select filter can be made non-removable.
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.removable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementAttributeContains(
      'css',
      $this->selector('field_taxonomy_target_id', '1'),
      'aria-disabled',
      'true',
    );

    // Test the select filter rewriting.
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.rewrite', "Alpha|rewrite\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementTextEquals(
      'css',
      $this->selector('field_taxonomy_target_id', '1'),
      'rewrite',
    );

    // Test that select filter rewriting to an empty value removes it from the
    // page.
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.rewrite', "Alpha|\r\nnull|null");
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('field_taxonomy_target_id', '1'));

    // Test that disabling the select filter removes it from the page.
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.rewrite', '');
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.enable', FALSE);
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', $this->selector('field_taxonomy_target_id', '1'));
  }

  /**
   * Test that the active filters display even when the view has no results.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testHideWhenNoResults(): void {
    $this->updateActiveFilterConfig('empty', FALSE);
    $this->loadView('test');
    $this->assertSession()->pageTextNotContains('Active Filters');
  }

  /**
   * Test that the heading can be visually hidden.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testHeading(): void {
    $this->updateActiveFilterConfig('hide_title', TRUE);
    $this->loadView('test');
    $this->assertSession()->elementAttributeContains(
      'css',
      '.active-filters dt',
      'class',
      'visually-hidden',
    );
  }

  /**
   * Test that the clear all button outputs correctly and can be disabled.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testClearAll(): void {
    // Test that the clear button exists.
    $this->loadView('test');
    $this->assertSession()->elementTextEquals(
      'css',
      '[data-active-filters-clear-all]',
      'Clear All Filters',
    );

    // Test that the clear button can be disabled.
    $this->updateActiveFilterConfig('clear_text', '');
    $this->loadView('test');
    $this->assertSession()->elementNotExists('css', '[data-active-filters-clear-all]');
  }

  /**
   * Test that active filters can be grouped by exposed filter.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGrouping(): void {
    $this->updateActiveFilterConfig('grouped', TRUE);

    // Test that the active filters heading displays separately.
    $this->loadView('test');
    $this->assertSession()->elementExists('css', '.active-filters__title');
    $this->assertSession()->elementsCount('css', '.active-filters__list', 1);
    $this->assertSession()->elementsCount('css', '.active-filters__group-title', 4);
    $this->assertSession()->elementsCount('css', '.active-filters__item', 5);
  }

  /**
   * Test that the module's assets are loaded correctly.
   */
  public function testAssetLoading(): void {
    // Test for the presence of CSS and JavaScript.
    $html = $this->loadView('test');
    self::assertStringContainsString('js/active-filters.js', $html);
    self::assertStringContainsString('css/active-filters.css', $html);
  }

  /**
   * Test that the correct number of hooks are invoked.
   */
  public function testHooksInvoked(): void {
    $state = \Drupal::state();
    $state->set('active_filters_test.count_hook_invocations', TRUE);
    $this->loadView('test');
    self::assertEquals(1, $state->get('active_filters_test.active_filters_alter'));
  }

  /**
   * Test that uninstalling the test module removes test entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testUninstall(): void {
    $entity_type_manager = $this->container->get('entity_type.manager');

    self::assertCount(1, $entity_type_manager->getStorage('node_type')->loadMultiple());
    self::assertCount(1, $entity_type_manager->getStorage('taxonomy_vocabulary')->loadMultiple());
    self::assertCount(10, $entity_type_manager->getStorage('node')->loadMultiple());
    self::assertCount(10, $entity_type_manager->getStorage('taxonomy_term')->loadMultiple());

    $this->container->get('module_installer')->uninstall(['active_filters_test']);
    self::assertCount(0, $entity_type_manager->getStorage('node_type')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('taxonomy_vocabulary')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('node')->loadMultiple());
    self::assertCount(0, $entity_type_manager->getStorage('taxonomy_term')->loadMultiple());
  }

}
