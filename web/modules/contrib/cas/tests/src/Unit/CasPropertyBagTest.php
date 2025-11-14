<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;

/**
 * CasPropertyBag unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\CasPropertyBag
 */
class CasPropertyBagTest extends UnitTestCase {

  /**
   * Test constructing a bag with a username.
   *
   * @covers ::__construct
   */
  public function testConstruct(): void {
    $name = $this->randomMachineName(8);
    $bag = new CasPropertyBag($name);
    $this->assertEquals($name, $bag->getUsername());
    $this->assertEquals($name, $bag->getOriginalUsername());
  }

  /**
   * Test setting a username.
   *
   * @covers ::setUsername
   */
  public function testSetUsername(): void {
    $name = $this->randomMachineName(8);
    $bag = new CasPropertyBag($name);
    $new_name = $this->randomMachineName(8);
    $bag->setUsername($new_name);
    $this->assertEquals($new_name, $bag->getUsername());
    $this->assertEquals($name, $bag->getOriginalUsername());
  }

  /**
   * Test setting a proxy granting ticket.
   *
   * @covers ::setPgt
   */
  public function testSetPgt(): void {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $pgt = $this->randomMachineName(24);
    $bag->setPgt($pgt);
    $this->assertEquals($pgt, $bag->getPgt());
  }

  /**
   * Test setting the attributes array.
   *
   * @covers ::setAttributes
   */
  public function testSetAttributes(): void {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $attributes = [
      'foo' => ['bar'],
      'baz' => ['quux, foobar'],
    ];
    $bag->setAttributes($attributes);
    $this->assertEquals($attributes, $bag->getAttributes());
  }

  /**
   * Test getting the username.
   *
   * @covers ::getUsername
   */
  public function testGetUsername(): void {
    $name = $this->randomMachineName(8);
    $bag = new CasPropertyBag($name);
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('username');
    $property->setAccessible(TRUE);
    $new_name = $this->randomMachineName(8);
    $property->setValue($bag, $new_name);
    $this->assertEquals($new_name, $bag->getUsername());
    $this->assertEquals($name, $bag->getOriginalUsername());
  }

  /**
   * Test getting the proxy granting ticket.
   *
   * @covers ::getPgt
   */
  public function testGetPgt(): void {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('pgt');
    $property->setAccessible(TRUE);
    $pgt = $this->randomMachineName(24);
    $property->setValue($bag, $pgt);
    $this->assertEquals($pgt, $bag->getPgt());
  }

  /**
   * Test getting the attributes.
   *
   * @covers ::getAttributes
   */
  public function testGetAttributes(): void {
    $bag = new CasPropertyBag($this->randomMachineName(8));
    $reflection = new \ReflectionClass($bag);
    $property = $reflection->getProperty('attributes');
    $property->setAccessible(TRUE);
    $attributes = [
      'foo' => ['bar'],
      'baz' => ['quux', 'foobar'],
    ];
    $property->setValue($bag, $attributes);
    $this->assertEquals($attributes, $bag->getAttributes());
  }

}
