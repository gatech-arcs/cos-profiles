<?php

declare(strict_types=1);

namespace Drupal\Tests\cas_attributes\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests the CAS Attributes module.
 *
 * @group cas_attributes
 */
class CasAttributesTest extends BrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas_attributes',
    'cas_mock_server',
  ];

  /**
   * Tests CAS Attributes.
   */
  public function testCasAttributes(): void {
    $this->config('cas_attributes.settings')
      ->set('sitewide_token_support', TRUE)
      ->save();

    $account = $this->createUser([], NULL, TRUE);
    $this->createCasUser('ian', 'ian@example.com', 'secret', [
      'attribute1' => 'value1',
      'attribute2' => 'value2',
    ], $account);
    $this->casLogin('ian@example.com', 'secret');
    $this->drupalGet('/admin/config/people/cas/attributes/available');
    $this->assertCasAttribute('email', '[cas:attribute:email]', 'ian@example.com');
    $this->assertCasAttribute('attribute1', '[cas:attribute:attribute1]', 'value1');
    $this->assertCasAttribute('attribute2', '[cas:attribute:attribute2]', 'value2');
  }

  /**
   * Asserts that attribute has been set as token.
   *
   * @param string $name
   *   The CAS attribute name.
   * @param string $token
   *   The token name.
   * @param string $value
   *   The CAS attribute value.
   */
  protected function assertCasAttribute(string $name, string $token, string $value): void {
    $xpath = '//tr/td[text() = "' . $name . '"]/following-sibling::td[text() = "' . $token . '"]/following-sibling::td[contains(text(), "' . $value . '")]';
    $this->assertSession()->elementExists('xpath', $xpath);
  }

}
