<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\csp\Unit\ConfigFactoryCacheableMetadataTrait;
use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\EventSubscriber\SettingsCspSubscriber;

/**
 * @coversDefaultClass \Drupal\csp\EventSubscriber\SettingsCspSubscriber
 * @group csp
 */
class SettingsCspSubscriberTest extends UnitTestCase {
  use ConfigFactoryCacheableMetadataTrait;

  /**
   * Data provider for boolean directive config test.
   *
   * @return array<string, array{mixed, boolean}>
   *   An array of test values.
   */
  public static function booleanDataProvider(): array {
    return [
      'TRUE' => [TRUE, TRUE],
      'FALSE' => [FALSE, FALSE],
      'NULL' => [NULL, FALSE],
      'Empty string' => ['', FALSE],
      'Zero' => [0, FALSE],
      'Number' => [1, TRUE],
      'Empty array' => [[], FALSE],
      'Array' => [['foo'], TRUE],
    ];
  }

  /**
   * Only a boolean directive with a true value should appear in the header.
   *
   * @dataProvider booleanDataProvider
   * @covers ::onCspPolicyAlter
   */
  public function testBooleanDirective(mixed $value, bool $expected): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => FALSE,
          'directives' => [],
        ],
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'upgrade-insecure-requests' => $value,
          ],
        ],
      ],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);

    $event = new PolicyAlterEvent(
      new Csp(),
      $response = new HtmlResponse(),
    );

    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals($expected, $event->getPolicy()->getDirective('upgrade-insecure-requests'));
  }

  /**
   * Check the policy with enforcement enabled.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testEnforcedResponse(): void {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
        'report-only' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);

    $event = new PolicyAlterEvent(
      new Csp(),
      $response = new HtmlResponse(),
    );

    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals("Content-Security-Policy", $event->getPolicy()->getHeaderName());
    $this->assertEquals("script-src 'self' 'unsafe-inline'; style-src 'self'", $event->getPolicy()->getHeaderValue());
  }

  /**
   * Check the generated headers with both policies enabled.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testBothPolicies(): void {

    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
            'style-src' => [
              'base' => 'any',
              'flags' => [
                'unsafe-inline',
              ],
            ],
          ],
        ],
        'enforce' => [
          'enable' => TRUE,
          'directives' => [
            'script-src' => [
              'base' => 'self',
            ],
            'style-src' => [
              'base' => 'self',
            ],
          ],
        ],
      ],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);

    $event = new PolicyAlterEvent(
      new Csp(),
      $response = new HtmlResponse(),
    );
    $subscriber->onCspPolicyAlter($event);
    $this->assertEquals("Content-Security-Policy", $event->getPolicy()->getHeaderName());
    $this->assertEquals("script-src 'self'; style-src 'self'", $event->getPolicy()->getHeaderValue());

    $policy = new Csp();
    $policy->reportOnly();
    $event = new PolicyAlterEvent(
      $policy,
      $response = new HtmlResponse(),
    );
    $subscriber->onCspPolicyAlter($event);
    $this->assertEquals("Content-Security-Policy-Report-Only", $event->getPolicy()->getHeaderName());
    $this->assertEquals("script-src * 'unsafe-inline'; style-src * 'unsafe-inline'", $event->getPolicy()->getHeaderValue());
  }

  /**
   * Config cache tags should be merged to response.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testCacheTags(): void {
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);
    $event = new PolicyAlterEvent(
      new Csp(),
      $response = new HtmlResponse(),
    );
    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals(['config:csp.settings'], $response->getCacheableMetadata()->getCacheTags());
  }

}
