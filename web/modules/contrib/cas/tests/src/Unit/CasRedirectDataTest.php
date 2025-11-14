<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasRedirectData;

/**
 * CasRedirectData unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\CasRedirectData
 */
class CasRedirectDataTest extends UnitTestCase {

  /**
   * Test the access methods.
   *
   * @covers ::setParameter
   * @covers ::getParameter
   * @covers ::getAllParameters
   */
  public function testParameters(): void {
    // Set the base login uri.
    $data = new CasRedirectData();

    // Test gateway set.
    $data->setParameter('gateway', 'true');
    $params = $data->getAllParameters();
    $this->assertEquals('true', $params['gateway']);

    // Test gateway removal.
    $data->setParameter('gateway', NULL);
    $params = $data->getAllParameters();
    $this->assertArrayNotHasKey('gateway', $params, 'Set values of null clear parameters');
  }

  /**
   * Test Service parameter setters and getters.
   *
   * @covers ::setServiceParameter
   * @covers ::getServiceParameter
   * @covers ::getAllServiceParameters
   */
  public function testServiceParameters(): void {
    $data = new CasRedirectData();

    $data->setServiceParameter('destination', 'node/1');
    $params = $data->getAllServiceParameters();
    $this->assertEquals('node/1', $params['destination']);
    $this->assertSame('node/1', $data->getServiceParameter('destination'));

    $data->setServiceParameter('destination', NULL);
    $params = $data->getAllServiceParameters();
    $this->assertArrayNotHasKey('destination', $params);
  }

  /**
   * Test Force/allow redirects.
   *
   * @covers ::willRedirect
   * @covers ::forceRedirection
   * @covers ::preventRedirection
   */
  public function testAllowRedirection(): void {
    $data = new CasRedirectData();

    $this->assertTrue($data->willRedirect(), 'Default Value');
    $data->forceRedirection();
    $this->assertTrue($data->willRedirect(), 'Forced');
    $data->preventRedirection();
    $this->assertFalse($data->willRedirect(), 'Prevented');
  }

  /**
   * Test Cacheable getter and setter.
   */
  public function testCacheable(): void {
    $data = new CasRedirectData();

    $this->assertFalse($data->getIsCacheable(), 'Default Value');

    $data->setIsCacheable(TRUE);
    $this->assertTrue($data->getIsCacheable(), 'Modified value');
  }

}
