<?php

namespace Drupal\Tests\fences\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormState;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the template override functionality.
 *
 * @group fences
 */
class OverrideTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'fences',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();

    $this->createContentType([
      'type' => 'page',
      'name' => 'Page',
    ]);

    // Add Fences third-party settings to the entity view display.
    $display = EntityViewDisplay::load('node.page.default');
    $body_component = $display->getComponent('body');
    $body_component['third_party_settings']['fences'] = [
      'fences_field_tag' => 'div',
      'fences_field_classes' => '',
      'fences_field_items_wrapper_tag' => 'none',
      'fences_field_items_wrapper_classes' => '',
      'fences_field_item_tag' => 'div',
      'fences_field_item_classes' => 'super-unique-test-class',
      'fences_label_tag' => 'div',
      'fences_label_classes' => '',
    ];
    $display->setComponent('body', $body_component);
    $display->save();

    $this->node = Node::create([
      'type' => 'page',
      'title' => $this->randomString(),
      'body' => $this->randomString(),
    ]);
    $this->node->save();
  }

  /**
   * Tests the template override functionality.
   *
   * @param string $theme
   *   The machine name of the theme to test.
   * @param bool $render_without_override
   *   Whether or not Fences markup should render without the override evoked.
   *
   * @dataProvider providerScenarios
   */
  public function testOverride($theme, $render_without_override) {
    if ($theme) {
      $this->switchTheme($theme);
    }

    $this->drupalGet('node/' . $this->node->id());
    if ($render_without_override) {
      $this->assertSession()->elementExists('css', '.super-unique-test-class');
    }
    else {
      $this->assertSession()->elementNotExists('css', '.super-unique-test-class');
    }

    $this->toggleOverrideSetting(TRUE);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->elementExists('css', '.super-unique-test-class');
  }

  /**
   * Provides scenarios.
   */
  public static function providerScenarios() {
    return [
      'core-wo-field-template' => [
        // Stark is the default theme for this test class.
        NULL,
        TRUE,
      ],
      'core-w-field-template' => [
        'olivero',
        TRUE,
      ],
      'contrib-wo-field-template' => [
        'fences_test_theme_a',
        TRUE,
      ],
      'contrib-w-field-template' => [
        'fences_test_theme_b',
        FALSE,
      ],
    ];
  }

  /**
   * Installs a theme and sets it as the site default theme.
   *
   * @param string $theme
   *   The theme machine name.
   */
  protected function switchTheme($theme): void {
    \Drupal::service('theme_installer')->install([$theme]);

    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', $theme)
      ->save();
  }

  /**
   * Toggles the template override setting.
   *
   * @param bool $enable
   *   Whether the override settings should be enabled or not.
   */
  protected function toggleOverrideSetting(bool $enable = TRUE): void {
    $form_state = new FormState();
    $values['fences_field_template_override_all_themes'] = $enable;
    $form_state->setValues($values);
    \Drupal::formBuilder()->submitForm('\Drupal\fences\Form\FencesConfigForm', $form_state);
  }

}
