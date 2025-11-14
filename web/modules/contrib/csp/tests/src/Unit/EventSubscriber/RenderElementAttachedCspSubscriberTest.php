<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\EventSubscriber\RenderElementAttachedCspSubscriber;
use Drupal\csp\PolicyHelper;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\csp\EventSubscriber\RenderElementAttachedCspSubscriber
 * @group csp
 */
class RenderElementAttachedCspSubscriberTest extends UnitTestCase {

  /**
   * Mock Policy Helper service.
   *
   * @var \Drupal\csp\PolicyHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  private PolicyHelper|MockObject $policyHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->policyHelper = $this->createMock(PolicyHelper::class);
  }

  /**
   * Directives in #attached[csp] should be added to the policy.
   */
  public function testAttachDirective(): void {
    $policy = $this->createMock(Csp::class);
    $policy->expects($this->once())
      ->method('fallbackAwareAppendIfEnabled')
      ->with('img-src', ['test.example.com']);

    $response = $this->createMock(HtmlResponse::class);
    $response->method('getAttachments')
      ->willReturn([
        'csp' => [
          'img-src' => ['test.example.com'],
        ],
      ]);

    $event = new PolicyAlterEvent($policy, $response);

    $subscriber = new RenderElementAttachedCspSubscriber($this->policyHelper);
    $subscriber->applyDirectives($event);
  }

  /**
   * Directives in #attached[csp] should be added to the policy.
   */
  public function testAttachMultipleDirectives(): void {
    $policy = $this->createMock(Csp::class);
    $expected = [
      'script-src-elem' => [Csp::POLICY_UNSAFE_INLINE],
      'img-src' => ['test.example.com'],
    ];
    $appendedDirectives = [];
    $appendedSources = [];
    $policy->expects($this->exactly(2))
      ->method('fallbackAwareAppendIfEnabled')
      ->with(
        $this->callback(function (string $directive) use ($expected, &$appendedDirectives): bool {
          $appendedDirectives[] = $directive;
          return array_key_exists($directive, $expected);
        }),
        $this->callback(function (array $sources) use ($expected, &$appendedSources): bool {
          $appendedSources[] = $sources;
          return in_array($sources, $expected);
        }),
      );

    $response = $this->createMock(HtmlResponse::class);
    $response->method('getAttachments')
      ->willReturn([
        'csp' => [
          'script-src-elem' => [Csp::POLICY_UNSAFE_INLINE],
          'img-src' => ['test.example.com'],
        ],
      ]);

    $event = new PolicyAlterEvent($policy, $response);

    $subscriber = new RenderElementAttachedCspSubscriber($this->policyHelper);
    $subscriber->applyDirectives($event);

    $this->assertEqualsCanonicalizing($expected, array_combine($appendedDirectives, $appendedSources));
  }

  /**
   * Data provider for valid nonceable directive keys.
   *
   * @return array<string, array<string>>
   *   An array of test values.
   */
  public static function attachNonceDirectiveProvider(): array {
    return [
      'script'          => ['script', 'script'],
      'script-src'      => ['script-src', 'script'],
      'script-src-elem' => ['script-src-elem', 'script'],
      'style'           => ['style', 'style'],
      'style-src'       => ['style-src', 'style'],
      'style-src-elem'  => ['style-src-elem', 'style'],
    ];
  }

  /**
   * Directives in #attached[csp_nonce] should have a nonce appended.
   *
   * @dataProvider attachNonceDirectiveProvider
   */
  public function testAttachNonce(string $directive, string $effectiveDirective): void {
    $policy = $this->createMock(Csp::class);

    $response = $this->createMock(HtmlResponse::class);
    $response->method('getAttachments')
      ->willReturn([
        'csp_nonce' => [
          $directive => ['test.example.com'],
        ],
      ]);

    $event = new PolicyAlterEvent($policy, $response);

    $this->policyHelper->expects($this->once())
      ->method('appendNonce')
      ->with(
        $policy,
        $effectiveDirective,
        ['test.example.com'],
      );

    $subscriber = new RenderElementAttachedCspSubscriber($this->policyHelper);
    $subscriber->applyNoncesAndHashes($event);
  }

  /**
   * Data provider for valid hashable directive keys.
   *
   * @return array<string, array<string>>
   *   An array of test values.
   */
  public static function attachHashDirectiveProvider(): array {
    return [
      'script-src'      => ['script-src', 'script', 'elem'],
      'script-src-elem' => ['script-src-elem', 'script', 'elem'],
      'script-src-attr' => ['script-src-attr', 'script', 'attr'],
      'style-src'       => ['style-src', 'style', 'elem'],
      'style-src-elem'  => ['style-src-elem', 'style', 'elem'],
      'style-src-attr'  => ['style-src-attr', 'style', 'attr'],
    ];
  }

  /**
   * Directives in #attached[csp_hash] should have a hash appended.
   *
   * @dataProvider attachHashDirectiveProvider
   */
  public function testAttachHash(
    string $directive,
    string $effectiveDirectiveType,
    string $effectiveDirectiveSubtype,
  ): void {
    $policy = $this->createMock(Csp::class);

    $response = $this->createMock(HtmlResponse::class);
    $response->method('getAttachments')
      ->willReturn([
        'csp_hash' => [
          $directive => ['sha256-test' => ['test.example.com']],
        ],
      ]);

    $event = new PolicyAlterEvent($policy, $response);

    $this->policyHelper->expects($this->once())
      ->method('appendHash')
      ->with(
        $policy,
        $effectiveDirectiveType,
        $effectiveDirectiveSubtype,
        ['test.example.com'],
        'sha256-test',
      );

    $subscriber = new RenderElementAttachedCspSubscriber($this->policyHelper);
    $subscriber->applyNoncesAndHashes($event);
  }

}
