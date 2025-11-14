<?php

namespace Drupal\Tests\fences_presets\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the fences_presets javascript functionalities.
 *
 * @group fences_presets
 */
class FencesPresetsFunctionalJsTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'fences_presets',
    'fences_presets_test_presets',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->drupalLogin($this->rootUser);
    $this->createContentType(['type' => 'article']);
  }

  /**
   * Tests that the preset gets applied.
   */
  public function testPresetGetsApplied() {
    $page = $this->getSession()->getPage();
    $session = $this->assertSession();
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    /** @var \Behat\Mink\Element\NodeElement $details */
    $details = $session->waitForElementVisible('css', 'details[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
    $this->assertNotEmpty($details);
    $details->click();
    /** @var \Behat\Mink\Element\NodeElement $presetSelect */
    $presetSelect = $session->waitForElementVisible('css', 'select.fences-preset-selector');
    // Select the 'none' preset:
    $presetSelect->selectOption('None');
    // There seems to be no way to wait for a finished "change" event, so we
    // simply wait the default 10000 milliseconds.
    $session->waitForElementVisible('css', '#does-not-exist');

    // See if all values are set properly:
    $fieldTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-tag"]');
    $this->assertEquals('none', $fieldTagSelect->getValue());

    $fieldClassesInput = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-classes"]');
    $this->assertEquals('', $fieldClassesInput->getValue());
    // The field should be invisible:
    $this->assertEquals(FALSE, $fieldClassesInput->isVisible());

    $fieldItemsWrapperTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-tag"]');
    $this->assertEquals('none', $fieldItemsWrapperTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-classes"]');
    $this->assertEquals('', $labelClasses->getValue());
    // The field should be invisible:
    $this->assertEquals(FALSE, $labelClasses->isVisible());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-tag"]');
    $this->assertEquals('none', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-classes"]');
    $this->assertEquals('', $labelClasses->getValue());
    // The field should be invisible:
    $this->assertEquals(FALSE, $labelClasses->isVisible());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-tag"]');
    $this->assertEquals('none', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-classes"]');
    $this->assertEquals('', $labelClasses->getValue());
    // The field should be invisible:
    $this->assertEquals(FALSE, $labelClasses->isVisible());

    // Select the test preset:
    $presetSelect->selectOption('Test preset');
    // There seems to be no way to wait for a finished "change" event, so we
    // simply wait the default 10000 milliseconds.
    $session->waitForElementVisible('css', '#does-not-exist');

    // See if the correct values are set:
    $fieldTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-tag"]');
    $this->assertEquals('div', $fieldTagSelect->getValue());

    $fieldClassesInput = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-classes"]');
    $this->assertEquals('field-class', $fieldClassesInput->getValue());
    // The field should be visible:
    $this->assertEquals(TRUE, $fieldClassesInput->isVisible());

    $fieldItemsWrapperTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-tag"]');
    $this->assertEquals('address', $fieldItemsWrapperTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-classes"]');
    $this->assertEquals('field-items-wrapper-class', $labelClasses->getValue());
    // The field should be visible:
    $this->assertEquals(TRUE, $labelClasses->isVisible());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-tag"]');
    $this->assertEquals('p', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-classes"]');
    $this->assertEquals('field-item-class', $labelClasses->getValue());
    // The field should be visible:
    $this->assertEquals(TRUE, $labelClasses->isVisible());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-tag"]');
    $this->assertEquals('details', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-classes"]');
    $this->assertEquals('label-class', $labelClasses->getValue());
    // The field should be visible:
    $this->assertEquals(TRUE, $labelClasses->isVisible());

    // Save the form:
    $page->pressButton('Update');
    $session->assertWaitOnAjaxRequest();
    $session->pageTextContains('You have unsaved changes.');
    $page->pressButton('Save');
    $session->pageTextContains('Your settings have been saved.');

    // Reload the page and see, if the values applied:
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    /** @var \Behat\Mink\Element\NodeElement $details */
    $details = $session->waitForElementVisible('css', 'details[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
    $this->assertNotEmpty($details);
    $details->click();
    /** @var \Behat\Mink\Element\NodeElement $presetSelect */
    $presetSelect = $session->waitForElementVisible('css', 'select.fences-preset-selector');

    // See if all the values are still set correctly:
    $fieldTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-tag"]');
    $this->assertEquals('div', $fieldTagSelect->getValue());

    $fieldClassesInput = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-classes"]');
    $this->assertEquals('field-class', $fieldClassesInput->getValue());

    $fieldItemsWrapperTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-tag"]');
    $this->assertEquals('address', $fieldItemsWrapperTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-items-wrapper-classes"]');
    $this->assertEquals('field-items-wrapper-class', $labelClasses->getValue());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-tag"]');
    $this->assertEquals('p', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-field-item-classes"]');
    $this->assertEquals('field-item-class', $labelClasses->getValue());

    $labelTagSelect = $session->elementExists('css', 'select[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-tag"]');
    $this->assertEquals('details', $labelTagSelect->getValue());

    $labelClasses = $session->elementExists('css', 'input[id*="edit-fields-body-settings-edit-form-third-party-settings-fences-fences-label-classes"]');
    $this->assertEquals('label-class', $labelClasses->getValue());
  }

}
