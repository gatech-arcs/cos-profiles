<?php

namespace Drupal\Tests\diff_plus\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Contains test cases for the default settings form.
 *
 * @group diff_plus
 */
class DiffPlusUserSettingsFormTest extends BrowserTestBase {

  /**
   * An associative array of expected default configuration values.
   *
   * @var array
   */
  protected const EXPECTED_DEFAULT_SETTINGS = [
    'enhance_diff_ui' => TRUE,
    'raw_html_indent_size' => 2,
    'raw_html_preserve_newlines' => FALSE,
    'raw_html_wrap_line_length' => 0,
    'raw_html_render_anonymously' => TRUE,
    'raw_html_strip_contextual_links' => TRUE,
    'raw_html_strip_js_view_dom_id' => TRUE,
    'raw_html_strip_comments' => TRUE,
    'raw_html_highlight_style' => '',
    'visual_html5_preserve_inline_styles' => TRUE,
  ];

  /**
   * An associative array of expected overridden default configuration values.
   *
   * @var array
   */
  protected const NEW_DEFAULT_VALUES_TO_SET = [
    'enhance_diff_ui' => FALSE,
    'raw_html_indent_size' => 3,
    'raw_html_preserve_newlines' => FALSE,
    'raw_html_wrap_line_length' => 50,
    'raw_html_render_anonymously' => TRUE,
    'raw_html_strip_contextual_links' => TRUE,
    'raw_html_strip_js_view_dom_id' => FALSE,
    'raw_html_strip_comments' => TRUE,
    'raw_html_highlight_style' => 'a11y-dark.min.css',
    'visual_html5_preserve_inline_styles' => FALSE,
  ];

  /**
   * An associative array of new configuration values to set.
   *
   * @var array
   */
  protected const NEW_VALUES_TO_SET = [
    'enhance_diff_ui' => TRUE,
    'raw_html_indent_size' => 4,
    'raw_html_preserve_newlines' => TRUE,
    'raw_html_wrap_line_length' => 250,
    'raw_html_render_anonymously' => FALSE,
    'raw_html_strip_contextual_links' => FALSE,
    'raw_html_strip_js_view_dom_id' => FALSE,
    'raw_html_strip_comments' => FALSE,
    'raw_html_highlight_style' => 'github.min.css',
    'visual_html5_preserve_inline_styles' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'diff_plus',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test case for the default settings form.
   *
   * @throws \Throwable
   *   If the test fails in any way.
   */
  public function testForm() {
    $assert = $this->assertSession();

    // Ensure that by default, users can't access the settings form.
    $this->drupalGet('/admin/config/content/diff_plus/settings');
    $assert->statusCodeEquals(403);

    // Sign in with a user that set defaults, but can't personalize settings.
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));
    $this->drupalGet('/admin/config/content/diff_plus/settings');
    $assert->statusCodeEquals(403);

    $page = $this->getSession()->getPage();

    // Sign in with a user that can personalize their own settings.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'personalize diff plus settings',
    ]));
    $this->drupalGet('/admin/config/content/diff_plus/settings');
    $assert->statusCodeEquals(200);

    // Ensure that the form has the correct default state.
    foreach (static::EXPECTED_DEFAULT_SETTINGS as $key => $value) {
      static::assertEquals(
        $value,
        $page->findField($key)->getValue()
      );
    }

    // Set new default values.
    $this->drupalGet('/admin/config/content/diff_plus/settings/default');
    foreach (static::NEW_DEFAULT_VALUES_TO_SET as $key => $value) {
      $page->findField($key)->setValue($value);
    }
    $this->submitForm([], 'Save default settings');

    $this->drupalGet('/admin/config/content/diff_plus/settings');
    foreach (static::NEW_DEFAULT_VALUES_TO_SET as $key => $value) {
      static::assertEquals(
        $value,
        $page->findField($key)->getValue()
      );
    }

    // Ensure that the form still has the correct default state.
    $this->drupalGet('/admin/config/content/diff_plus/settings');
    foreach (static::NEW_DEFAULT_VALUES_TO_SET as $key => $value) {
      static::assertEquals(
        $value,
        $page->findField($key)->getValue()
      );
    }

    // Set up some personalized settings.
    foreach (static::NEW_VALUES_TO_SET as $key => $value) {
      $page->findField($key)->setValue($value);
    }

    // Save the form.
    $this->submitForm([], 'Save my personal settings');

    // Ensure that the form has persisted the new state.
    foreach (static::NEW_VALUES_TO_SET as $key => $value) {
      static::assertEquals(
        $value,
        $page->findField($key)->getValue()
      );
    }
  }

}
