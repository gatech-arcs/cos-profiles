<?php

namespace Drupal\Tests\csp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the nonce functionality.
 *
 * @group csp
 */
class NonceSettingTest extends BrowserTestBase {

  /**
   * Set default theme to stark.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['csp', 'csp_test'];

  /**
   * A nonce should not be added to drupalSettings without the library.
   */
  public function testSettingsWithoutNonce(): void {
    $this->drupalGet('csp-test/no-settings-nonce');
    $this->assertEquals(200, $this->getSession()->getStatusCode());
    $jsSettings = $this->getDrupalSettings();
    $this->assertArrayNotHasKey('csp', $jsSettings);
  }

  /**
   * A nonce value is added to drupalSettings.
   */
  public function testSettingsHasNonce(): void {
    $this->drupalGet('csp-test/settings-nonce');
    $jsSettings = $this->getDrupalSettings();
    $this->assertArrayHasKey('csp', $jsSettings);
    $this->assertArrayHasKey('nonce', $jsSettings['csp']);
  }

}
