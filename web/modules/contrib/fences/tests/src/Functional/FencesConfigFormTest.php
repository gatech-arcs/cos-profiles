<?php

namespace Drupal\Tests\fences\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the FencesConfigForm form.
 *
 * @group fences
 */
class FencesConfigFormTest extends BrowserTestBase {


  /**
   * The non-admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $nonAdminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fences',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setup() : void {
    parent::setup();

    // Create an admin user.
    $this->adminUser = $this
      ->drupalCreateUser([
        'access administration pages',
        'administer fences settings',
      ]);
    // Create a non-admin user.
    $this->nonAdminUser = $this
      ->drupalCreateUser([
        'access administration pages',
      ]);
  }

  /**
   * Test route permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('/admin/config/user-interface/fences/settings');
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/user-interface/fences/settings');
    $assert_session->statusCodeEquals(200);
  }

  /**
   * Test form submission and config storage.
   */
  public function testForm() {
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->adminUser);

    // Upon initial config install, setting is disabled.
    $this->assertFalse(\Drupal::config('fences.settings')->get('fences_field_template_override_all_themes'));

    // Test enabling the setting.
    $this->drupalGet('/admin/config/user-interface/fences/settings');
    $page->checkField('fences_field_template_override_all_themes');
    $page->pressButton('edit-submit');

    $this->assertTrue(\Drupal::config('fences.settings')->get('fences_field_template_override_all_themes'));

    // Test disabling the setting.
    $this->drupalGet('/admin/config/user-interface/fences/settings');
    $page->uncheckField('fences_field_template_override_all_themes');
    $page->pressButton('edit-submit');

    $this->assertFalse(\Drupal::config('fences.settings')->get('fences_field_template_override_all_themes'));
  }

}
