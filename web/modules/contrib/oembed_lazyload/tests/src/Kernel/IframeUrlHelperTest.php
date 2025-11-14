<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_lazyload\IframeUrlHelper;

/**
 * Test cases pertaining to the iframe url helper.
 *
 * @group oembed_lazyload
 */
class IframeUrlHelperTest extends KernelTestBase {

  /**
   * The mock private key.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The subject under test.
   *
   * @var \Drupal\oembed_lazyload\IframeUrlHelper
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->privateKey = $this->createMock(PrivateKey::class);

    $this->instance = new IframeUrlHelper($this->privateKey);
  }

  /**
   * Tests that the hash generation method works as expected.
   */
  public function testGetHash() {
    $expected = Crypt::hmacBase64('test:a:b:c', $this->privateKey->get() . Settings::getHashSalt());
    $actual = $this->instance->getHash('test', ['a', 'b', 'c']);

    static::assertSame($expected, $actual);
  }

}
