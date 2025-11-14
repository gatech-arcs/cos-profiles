<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oembed_lazyload\Access\OembedLazyloadIframeAccessCheck;
use Drupal\oembed_lazyload\IframeUrlHelper;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test cases pertaining to the access check.
 *
 * @group oembed_lazyload
 */
class OEmbedLazyloadIframeAccessCheckTest extends KernelTestBase {

  /**
   * The oembed lazyload iFrame URL helper service.
   *
   * @var \Drupal\oembed_lazyload\IframeUrlHelper
   */
  protected $oembedLazyloadIframeUrlHelper;

  /**
   * The mock query.
   *
   * @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface
   */
  protected $query;

  /**
   * The subject under test.
   *
   * @var \Drupal\oembed_lazyload\Access\OembedLazyloadIframeAccessCheck
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->oembedLazyloadIframeUrlHelper = $this->createMock(IframeUrlHelper::class);

    $request_stack = $this->createMock(RequestStack::class);

    /** @var \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject $request */
    $request = $this->createMock(Request::class);

    $request_stack->method('getCurrentRequest')
      ->willReturn($request);

    $this->query = new InputBag();

    $request->query = $this->query;

    $this->instance = new OembedLazyloadIframeAccessCheck($this->oembedLazyloadIframeUrlHelper, $request_stack);
  }

  /**
   * Tests that the access check is "neutral" when it should not be involved.
   *
   * Note - since this is a routing access check, allowed must be returned...
   */
  public function testNeutral() {

    $expected = AccessResult::allowed()
      ->addCacheContexts([
        'url.query_args:oembed_lazyload',
      ]);

    $this->query->replace([]);

    static::assertEquals($expected, $this->instance->access());
  }

  /**
   * Tests that the access check forbids access if there is a hash mismatch.
   */
  public function testForbidden() {

    $this->query->replace([
      'oembed_lazyload' => '1',
      'oembed_lazyload_hash' => 'some hash',
      'provider' => 'some provider',
      'options' => ['some' => 'options'],
    ]);

    $this->oembedLazyloadIframeUrlHelper
      ->method('getHash')
      ->willReturn('some other hash');

    $expected = AccessResult::forbidden()
      ->addCacheContexts([
        'url.query_args:oembed_lazyload_hash',
        'url.query_args:provider',
        'url.query_args:options',
        'url.query_args:oembed_lazyload',
      ]);

    static::assertEquals($expected, $this->instance->access());
  }

  /**
   * Tests that the access check allows access if there is a hash match.
   */
  public function testAllowed() {
    $this->query->replace([
      'oembed_lazyload' => '1',
      'oembed_lazyload_hash' => 'some hash',
      'provider' => 'some provider',
      'options' => ['some' => 'options'],
    ]);

    $this->oembedLazyloadIframeUrlHelper
      ->method('getHash')
      ->willReturn('some hash');

    $expected = AccessResult::allowed()
      ->addCacheContexts([
        'url.query_args:oembed_lazyload_hash',
        'url.query_args:provider',
        'url.query_args:options',
        'url.query_args:oembed_lazyload',
      ]);
    static::assertEquals($expected, $this->instance->access());
  }

}
