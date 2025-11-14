<?php

namespace Drupal\Tests\fences\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests fences_requirements().
 *
 * @group fences
 */
class RequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fences',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['fences']);
  }

  /**
   * Tests fences_requirements().
   */
  public function testRequirements() {
    \Drupal::service('module_handler')->loadInclude('fences', 'install');

    // Install a theme without a field.html.twig file.
    \Drupal::service('theme_installer')->install(['fences_test_theme_a']);
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertSame($requirements['description']->render(), 'By default, the Fences module only overrides the field template (field.html.twig) for core themes. When the <em>Override the field template for all themes</em> setting is enabled, Fences will override the field template for all themes (both core and config).');
    $this->assertSame($requirements['value']->render(), 'No contrib themes provide a <em>field.html.twig</em> template.');
    $this->assertSame($requirements['severity'], REQUIREMENT_OK);

    // Install a theme with a field.html.twig file.
    \Drupal::service('theme_installer')->install(['fences_test_theme_b']);
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertSame($requirements['description']->render(), 'By default, the Fences module only overrides the field template (field.html.twig) for core themes. When the <em>Override the field template for all themes</em> setting is enabled, Fences will override the field template for all themes (both core and config).');
    $this->assertSame($requirements['value']->render(), 'The following contrib themes provide a <em>field.html.twig</em> template: fences_test_theme_b.');
    $this->assertSame($requirements['severity'], REQUIREMENT_WARNING);

    // Install a theme, who's base theme has a field.html.twig file.
    \Drupal::service('theme_installer')->uninstall(['fences_test_theme_b']);
    \Drupal::service('theme_installer')->install(['fences_test_theme_b_1']);
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertSame($requirements['description']->render(), 'By default, the Fences module only overrides the field template (field.html.twig) for core themes. When the <em>Override the field template for all themes</em> setting is enabled, Fences will override the field template for all themes (both core and config).');
    $this->assertSame($requirements['value']->render(), 'The following contrib themes provide a <em>field.html.twig</em> template: fences_test_theme_b.');
    $this->assertSame($requirements['severity'], REQUIREMENT_WARNING);

    // Enable override setting.
    $this->container->get('config.factory')
      ->getEditable('fences.settings')
      ->set('fences_field_template_override_all_themes', TRUE)
      ->save();
    $requirements = fences_requirements('runtime')['fences'];

    $this->assertNull($requirements['description']);
    $this->assertSame($requirements['value']->render(), 'The <em>Override the field template for all themes</em> setting is enabled. All <em>field.html.twig</em> templates are overridden.');
    $this->assertSame($requirements['severity'], REQUIREMENT_OK);
  }

}
