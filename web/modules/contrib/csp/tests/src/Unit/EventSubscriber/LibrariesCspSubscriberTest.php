<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\EventSubscriber\LibrariesCspSubscriber;
use Drupal\csp\LibraryPolicyBuilder;

/**
 * @coversDefaultClass \Drupal\csp\EventSubscriber\LibrariesCspSubscriber
 * @group csp
 */
class LibrariesCspSubscriberTest extends UnitTestCase {

  /**
   * Test that library sources are appended.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testAppendingLibrarySources(): void {
    $libraryPolicyBuilder = $this->createMock(LibraryPolicyBuilder::class);

    $libraryPolicyBuilder->expects($this->any())
      ->method('getSources')
      ->willReturn([
        'style-src' => ['example.com'],
        'style-src-elem' => ['example.com'],
      ]);

    $subscriber = new LibrariesCspSubscriber($libraryPolicyBuilder);

    $policy = new Csp();
    $policy->reportOnly();
    $policy->setDirective('script-src', [Csp::POLICY_ANY, Csp::POLICY_UNSAFE_INLINE]);
    $policy->setDirective('style-src', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    $policy->setDirective('style-src-elem', [Csp::POLICY_SELF]);
    $event = new PolicyAlterEvent(
      $policy,
      new HtmlResponse(),
    );
    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals('Content-Security-Policy-Report-Only', $policy->getHeaderName());
    $this->assertEquals("script-src * 'unsafe-inline'; style-src 'self' 'unsafe-inline' example.com; style-src-elem 'self' example.com", $policy->getHeaderValue());
  }

  /**
   * Test that library sources do not override a disabled directive.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testDisabledLibraryDirective(): void {
    $libraryPolicyBuilder = $this->createMock(LibraryPolicyBuilder::class);

    $libraryPolicyBuilder->expects($this->any())
      ->method('getSources')
      ->willReturn([
        'style-src' => ['example.com'],
        'style-src-elem' => ['example.com'],
      ]);

    $subscriber = new LibrariesCspSubscriber($libraryPolicyBuilder);

    $policy = new Csp();
    $policy->reportOnly();
    $policy->setDirective('script-src', [Csp::POLICY_ANY, Csp::POLICY_UNSAFE_INLINE]);
    $policy->setDirective('style-src', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);
    // script-src-elem is not set.
    $event = new PolicyAlterEvent(
      $policy,
      new HtmlResponse(),
    );
    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals('Content-Security-Policy-Report-Only', $policy->getHeaderName());
    // script-src-elem should not be present.
    $this->assertEquals("script-src * 'unsafe-inline'; style-src 'self' 'unsafe-inline' example.com", $policy->getHeaderValue());
  }

}
