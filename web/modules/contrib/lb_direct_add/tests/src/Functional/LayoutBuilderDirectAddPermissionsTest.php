<?php

namespace Drupal\Tests\lb_direct_add\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Contains test case for testing admin permission.
 *
 * @group lb_direct_add
 */
class LayoutBuilderDirectAddPermissionsTest extends BrowserTestBase {
  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $nonAdminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lb_direct_add',
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
    $this->adminUser = $this->drupalCreateUser(['administer layout builder direct add settings']);
    $this->nonAdminUser = $this->drupalCreateUser();
  }

  /**
   * Tests that a user with admin permission can access the admin page.
   */
  public function testAdminPermission() {
    $assert = $this->assertSession();

    // Non-admin user should not be able to access the admin page.
    $this->drupalLogin($this->nonAdminUser);
    $this->drupalGet('admin/config/content/layout-builder-direct-add');
    $assert->statusCodeEquals(403);

    // Admin user should be able to access the admin page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/layout-builder-direct-add');
    $assert->statusCodeEquals(200);
  }

}
