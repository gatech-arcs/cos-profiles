<?php

namespace Drupal\Tests\csp\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\csp\Nonce;

/**
 * Test the Nonce service.
 *
 * @coversDefaultClass \Drupal\csp\Nonce
 * @group csp
 */
class NonceTest extends UnitTestCase {

  /**
   * The nonce value should be statically cached.
   */
  public function testValue(): void {
    $nonce = new Nonce();

    $value1 = $nonce->getValue();
    $value2 = $nonce->getValue();

    $this->assertEquals($value1, $value2);
  }

  /**
   * The source value should be properly formatted.
   */
  public function testSource(): void {
    $nonce = new Nonce();

    // 16 bytes will encode to ceil(16 * 8/6) = 22 characters.
    $this->assertMatchesRegularExpression(
      "/'nonce-[A-Za-z0-9_-]{22}'/",
      $nonce->getSource()
    );
  }

}
