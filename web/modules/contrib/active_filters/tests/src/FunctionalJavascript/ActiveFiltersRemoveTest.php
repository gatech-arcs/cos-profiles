<?php

declare(strict_types=1);

namespace Drupal\Tests\active_filters\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\active_filters\Traits\ActiveFiltersFunctionalTestsTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the JavaScript which removes Active Filters.
 */
#[Group('active_filters')]
class ActiveFiltersRemoveTest extends WebDriverTestBase {

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
    'block',
    'node',
    'options',
    'taxonomy',
    'views',
  ];

  /**
   * Test removing active filters from core widgets with and without AJAX.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testCoreWidgets(): void {
    // Set boolean groups as optional so that they are removable.
    $this->updateViewConfig(
      'display.test.display_options.filters.field_boolean_value.group_info.optional',
      TRUE,
    );
    $this->updateViewConfig(
      'display.test_ajax.display_options.filters.field_boolean_value.group_info.optional',
      TRUE,
    );

    $this->loadView('test');
    $session = $this->assertSession();

    // Check that text active filters exist and remove correctly.
    $title = $session->waitForElement('css', $this->selector('title', 'Title'));
    self::assertNotNull($title);
    $title->click();
    $session->waitForText('Active Filters');
    $session->elementNotExists('css', $this->selector('title', 'Title'));
    $session->fieldValueEquals('title', '');

    // Check that boolean active filters exist and remove correctly.
    $session->elementExists('css', $this->selector('field_boolean_value', '2'))->click();
    $session->waitForText('Active Filters');
    $session->elementNotExists('css', $this->selector('field_boolean_value', '2'));
    $session->fieldValueEquals('field_boolean_value', 'All');

    // Check that single select active filters exist and remove correctly.
    $taxonomy = $session->waitForElement('css', $this->selector('field_taxonomy_target_id', '1'));
    self::assertNotNull($taxonomy);
    $taxonomy->click();
    $session->waitForText('Active Filters');
    $session->elementNotExists('css', $this->selector('field_taxonomy_target_id', '1'));
    $session->fieldValueEquals('field_taxonomy_target_id', 'All');

    // Check that multiselect active filters exist and remove correctly.
    $session->elementExists('css', $this->selector('field_list_text_value', 'a'));
    $session->elementExists('css', $this->selector('field_list_text_value', 'b'))->click();
    $session->waitForText('Active Filters');
    $session->elementExists('css', $this->selector('field_list_text_value', 'a'));
    $session->elementNotExists('css', $this->selector('field_list_text_value', 'b'));
    $page = $this->getSession()->getPage();
    self::assertTrue($page->find('css', 'option[value="a"]')?->isSelected());
    self::assertFalse($page->find('css', 'option[value="b"]')?->isSelected());

    // Test with AJAX.
    $this->loadView('ajax', FALSE);
    $session = $this->assertSession();

    // Check that text active filters exist and remove correctly.
    $title = $session->waitForElement('css', '[name="title"]');
    self::assertNotNull($title);
    $title->setValue('Title');
    $session->elementExists('css', '[type="submit"]')->click();
    $session->assertWaitOnAjaxRequest();
    $title = $session->waitForElement('css', $this->selector('title', 'Title'));
    self::assertNotNull($title);
    $title->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementNotExists('css', $this->selector('title', 'Title'));
    $session->fieldValueEquals('title', '');

    // Check that boolean active filters exist and remove correctly.
    $boolean = $session->waitForElement('css', '[name="field_boolean_value"]');
    self::assertNotNull($boolean);
    $boolean->setValue('2');
    $session->elementExists('css', '[type="submit"]')->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementExists('css', $this->selector('field_boolean_value', '2'))->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementNotExists('css', $this->selector('field_boolean_value', '2'));
    $session->fieldValueEquals('field_boolean_value', 'All');

    // Check that single select active filters exist and remove correctly.
    $taxonomy = $session->waitForElement('css', '[name="field_taxonomy_target_id"]');
    self::assertNotNull($taxonomy);
    $taxonomy->setValue('1');
    $session->elementExists('css', '[type="submit"]')->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementExists('css', $this->selector('field_taxonomy_target_id', '1'))->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementNotExists('css', $this->selector('field_taxonomy_target_id', '1'));
    $session->fieldValueEquals('field_taxonomy_target_id', 'All');

    // Check that multiselect active filters exist and remove correctly.
    $taxonomy = $session->waitForElement('css', '[name="field_list_text_value[]"]');
    self::assertNotNull($taxonomy);
    $taxonomy->setValue('a');
    $session->elementExists('css', '[type="submit"]')->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementExists('css', $this->selector('field_list_text_value', 'a'))->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementNotExists('css', $this->selector('field_list_text_value', 'a'));
    $page = $this->getSession()->getPage();
    self::assertFalse($page->find('css', 'option[value="a"]')?->isSelected());
  }

  /**
   * Test that the clear all button clears all active filters.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testClearAll(): void {
    // Set boolean groups as optional so that they are removable.
    $this->updateViewConfig(
      'display.test.display_options.filters.field_boolean_value.group_info.optional',
      TRUE,
    );
    $this->updateViewConfig(
      'display.test_ajax.display_options.filters.field_boolean_value.group_info.optional',
      TRUE,
    );

    // Rewrite "All" values so that they do not display.
    $this->updateActiveFilterConfig('filters.field_boolean_value.rewrite', '- Any -|');
    $this->updateActiveFilterConfig('filters.field_taxonomy_target_id.rewrite', '- Any -|');

    // Test without AJAX.
    $this->loadView('test');
    $session = $this->assertSession();
    $clear = $session->waitForElement('css', '[data-active-filters-clear-all]');
    self::assertNotNull($clear);
    $clear->click();
    $session->waitForText('title');
    $session->elementNotExists('css', '[data-active-filter-name]');

    // Test with AJAX.
    $this->loadView('ajax', FALSE);
    $session = $this->assertSession();
    $title = $session->waitForElement('css', '[name="title"]');
    self::assertNotNull($title);
    $title->setValue('Title');
    $boolean = $session->waitForElement('css', '[name="field_boolean_value"]');
    self::assertNotNull($boolean);
    $boolean->setValue('2');
    $taxonomy = $session->waitForElement('css', '[name="field_taxonomy_target_id"]');
    self::assertNotNull($taxonomy);
    $taxonomy->setValue('1');
    $list = $session->waitForElement('css', '[name="field_list_text_value[]"]');
    self::assertNotNull($list);
    $list->setValue('a');
    $submit = $session->waitForElement('css', '[type="submit"]');
    self::assertNotNull($submit);
    $submit->click();
    $session->assertWaitOnAjaxRequest();
    $clear = $session->waitForElement('css', '[data-active-filters-clear-all]');
    self::assertNotNull($clear);
    $clear->click();
    $session->assertWaitOnAjaxRequest();
    $session->elementNotExists('css', '[data-active-filter-name]');
  }

  /**
   * Test that active filter removal methods are called.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRemoveOverride(): void {
    $this->loadView('test');
    $session = $this->assertSession();

    // Since the override returns null the active filter will still be applied.
    $this->getSession()->executeScript('document.querySelector(\'[name="title"]\').activeFilterRemove = () => null;');
    $title = $session->waitForElement('css', $this->selector('title', 'Title'));
    self::assertNotNull($title);
    $title->click();
    $session->elementExists('css', $this->selector('title', 'Title'));
    $session->fieldValueEquals('title', 'Title');
  }

  /**
   * Test that active filters still remove if there are duplicate elements.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testDuplicateInputName(): void {
    $this->drupalPlaceBlock('active_filters_test_title_form', [
      'region' => 'content',
      'weight' => -100,
    ]);

    $this->loadView('test');
    $session = $this->assertSession();

    // Check that two title fields exist on the page.
    $session->elementsCount('css', '[name="title"]', 2);

    // Check that text active filters exist and remove correctly.
    $title = $session->waitForElement('css', $this->selector('title', 'Title'));
    self::assertNotNull($title);
    $title->click();
    $session->waitForText('Active Filters');
    $session->elementNotExists('css', $this->selector('title', 'Title'));
    $session->fieldValueEquals('title', '');
  }

}
