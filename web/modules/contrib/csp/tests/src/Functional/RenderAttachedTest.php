<?php

namespace Drupal\Tests\csp\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

// cspell:ignore Tyrell

/**
 * Tests the render #attached functionality.
 *
 * @group csp
 */
class RenderAttachedTest extends BrowserTestBase {

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
   * User for testing authenticated requests.
   *
   * @var \Drupal\user\Entity\User
   */
  private User $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('csp.settings')
      ->set('enforce', [
        'enable' => TRUE,
        'directives' => [
          'default-src' => [
            'base' => 'self',
          ],
        ],
        'reporting' => [
          'plugin' => 'none',
        ],
      ])
      ->save();

    $this->config('csp.settings')
      ->set('report-only', [
        'enable' => FALSE,
        'directives' => [],
        'reporting' => [
          'plugin' => 'none',
        ],
      ])
      ->save();

    // Create a test user.
    // @phpstan-ignore assign.propertyType
    $this->user = $this->createUser([], 'Garnett Tyrell');
  }

  /**
   * Attached directives should be added to the header.
   */
  public function testDirectives(): void {
    $this->drupalGet('csp-test/element-directives');
    $header = $this->getSession()->getResponseHeader('Content-Security-Policy');

    $this->assertStringContainsString("img-src 'self' https://img.example.com", $header);
    $this->assertStringContainsString("font-src 'self' https://fonts.example.com", $header);
  }

  /**
   * Authenticated users must get a unique nonce per request.
   */
  public function testAuthenticatedElementNonce(): void {
    $this->drupalLogin($this->user);

    $nonces = [];

    for ($i = 0; $i < 5; $i++) {
      $this->drupalGet('csp-test/element-nonce');
      $nonceElement = $this->cssSelect('#nonce');
      $this->assertNotEmpty($nonceElement);

      $nonce = $nonceElement[0]->getText();
      $this->assertFalse(in_array($nonce, $nonces));
    }
  }

  /**
   * The page cache may repeat nonces for anonymous users.
   */
  public function testAnonymousElementNonce(): void {
    $lastNonce = '';

    for ($i = 0; $i < 5; $i++) {
      $this->drupalGet('csp-test/element-nonce');
      $nonceElement = $this->cssSelect('#nonce');
      $this->assertNotEmpty($nonceElement);

      $header = $this->getSession()->getResponseHeader('Content-Security-Policy');
      $this->assertMatchesRegularExpression("<'nonce-([-A-Za-z0-9+/_]{22,}={0,2})'>i", $header);

      if ($lastNonce) {
        $this->assertEquals($lastNonce, $nonceElement[0]->getText());
      }
      $lastNonce = $nonceElement[0]->getText();
    }
  }

  /**
   * Attached hashes should be added to the header.
   */
  public function testElementHash(): void {
    $this->drupalGet('csp-test/element-hash');
    $header = $this->getSession()->getResponseHeader('Content-Security-Policy');

    $this->assertStringContainsString("'sha256-m0zKW3SgFyV1D9aL5SVP9sTjV8ymQ9XirnpSfOsqCFk='", $header);
  }

}
