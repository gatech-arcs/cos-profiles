<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\csp\Unit\ConfigFactoryCacheableMetadataTrait;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\EventSubscriber\ResponseCspSubscriber;
use Drupal\csp\Nonce;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\csp\EventSubscriber\ResponseCspSubscriber
 * @group csp
 */
class ResponseCspSubscriberTest extends UnitTestCase {
  use ConfigFactoryCacheableMetadataTrait;

  /**
   * Mock HTTP Response.
   *
   * @var \Drupal\Core\Render\HtmlResponse|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $response;

  /**
   * Mock Response Event.
   *
   * @var \Symfony\Component\HttpKernel\Event\ResponseEvent|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $event;

  /**
   * The Event Dispatcher Service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * The Nonce service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\csp\Nonce
   */
  private $nonce;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->response = $this->createMock(HtmlResponse::class);
    $this->response->headers = $this->createMock(ResponseHeaderBag::class);
    $responseCacheableMetadata = $this->createMock(CacheableMetadata::class);
    $this->response->method('getCacheableMetadata')
      ->willReturn($responseCacheableMetadata);

    $this->event = new ResponseEvent(
      $this->createMock(HttpKernelInterface::class),
      $this->createMock(Request::class),
      HttpKernelInterface::MAIN_REQUEST,
      $this->response
    );

    $this->eventDispatcher = $this->createMock(EventDispatcher::class);

    $this->nonce = $this->createMock(Nonce::class);
  }

  /**
   * Check that the subscriber listens to the Response event.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEvents(): void {
    $this->assertArrayHasKey(KernelEvents::RESPONSE, ResponseCspSubscriber::getSubscribedEvents());
  }

  /**
   * Check that Policy Alter events are dispatched.
   *
   * @covers ::onKernelResponse
   */
  public function testPolicyAlterEvent(): void {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'style-src' => [
              'base' => 'any',
            ],
          ],
        ],
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $this->eventDispatcher->expects($this->exactly(2))
      ->method('dispatch')
      ->with(
        $this->isInstanceOf(PolicyAlterEvent::class),
        $this->equalTo(CspEvents::POLICY_ALTER)
      )
      ->willReturnCallback(function ($event, $eventName) {
        $policy = $event->getPolicy();
        $policy->setDirective('font-src', [Csp::POLICY_SELF]);
        return $event;
      });

    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheableDependency')
      ->with($this->callback(function (CacheableDependencyInterface $cacheableDependency) {
        return in_array('config:csp.settings', $cacheableDependency->getCacheTags());
      }));

    $this->response->headers->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function (string $name, string $value) {
        match ($name) {
          'Content-Security-Policy-Report-Only', 'Content-Security-Policy' => $this->assertEquals("font-src 'self'", $value),
          default => $this->fail("Unexpected Header"),
        };
      });

    $subscriber = new ResponseCspSubscriber(
      $configFactory,
      $this->eventDispatcher,
      $this->nonce
    );

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * An empty or missing directive list should not output a header.
   *
   * @covers ::onKernelResponse
   */
  public function testEmptyDirective(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [],
        ],
        'enforce' => [
          'enable' => TRUE,
        ],
      ],
    ]);

    $subscriber = new ResponseCspSubscriber(
      $configFactory,
      $this->eventDispatcher,
      $this->nonce,
    );

    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheableDependency')
      ->with($this->callback(function (CacheableDependencyInterface $cacheableDependency) {
        return in_array('config:csp.settings', $cacheableDependency->getCacheTags());
      }));

    $this->response->headers->expects($this->never())
      ->method('set');

    $subscriber->onKernelResponse($this->event);
  }

  /**
   * Preexisting headers for disabled policies should be removed.
   *
   * @covers ::onKernelResponse
   */
  public function testRemoveDefaultPolicy(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $subscriber = new ResponseCspSubscriber(
      $configFactory,
      $this->eventDispatcher,
      $this->nonce,
    );

    $this->response->getCacheableMetadata()
      ->expects($this->once())
      ->method('addCacheableDependency')
      ->with($this->callback(function (CacheableDependencyInterface $cacheableDependency) {
        return in_array('config:csp.settings', $cacheableDependency->getCacheTags());
      }));

    $headersToRemove = ['Content-Security-Policy-Report-Only', 'Content-Security-Policy'];
    $this->response->headers->expects($this->exactly(2))
      ->method('remove')
      ->willReturnCallback(function (string $name) use (&$headersToRemove) {
        if (!in_array($name, $headersToRemove)) {
          $this->fail("Unexpected Header Removed");
        }
        $headersToRemove = array_diff($headersToRemove, [$name]);
      });

    $this->response->headers->expects($this->never())
      ->method('set');

    $subscriber->onKernelResponse($this->event);

    if (!empty($headersToRemove)) {
      $this->fail('Expected header not removed: ' . implode(', ', $headersToRemove));
    }
  }

}
