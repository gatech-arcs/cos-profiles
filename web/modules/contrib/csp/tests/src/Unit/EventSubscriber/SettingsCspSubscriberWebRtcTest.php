<?php

namespace Drupal\Tests\csp\Unit\EventSubscriber;

use Drupal\Tests\UnitTestCase;
use Drupal\csp\Csp;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\EventSubscriber\SettingsCspSubscriber;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test formatting of WebRTC directive from config.
 *
 * @coversDefaultClass \Drupal\csp\EventSubscriber\SettingsCspSubscriber
 * @group csp
 */
class SettingsCspSubscriberWebRtcTest extends UnitTestCase {

  /**
   * Check that empty webrtc config does not enable directive.
   *
   * @covers ::onCspPolicyAlter
   */
  public function testEmptyWebRtc(): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'webrtc' => '',
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);
    $policy = new Csp();
    $event = new PolicyAlterEvent($policy, $this->createMock(Response::class));

    $subscriber->onCspPolicyAlter($event);

    $this->assertFalse($policy->hasDirective('webrtc'));
  }

  /**
   * Data provider for WebRTC config values.
   *
   * @return array<string, array{string}>
   *   Configuration values.
   */
  public static function webRtcConfigProvider(): array {
    return [
      'allow' => ['allow'],
      'block' => ['block'],
    ];
  }

  /**
   * Check that webrtc directive is formatted correctly.
   *
   * @covers ::onCspPolicyAlter
   * @dataProvider webRtcConfigProvider
   */
  public function testWebRtc(string $value): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'csp.settings' => [
        'report-only' => [
          'enable' => TRUE,
          'directives' => [
            'webrtc' => $value,
          ],
        ],
        'enforce' => [
          'enable' => FALSE,
        ],
      ],
    ]);

    $subscriber = new SettingsCspSubscriber($configFactory);
    $policy = new Csp();
    $policy->reportOnly();
    $event = new PolicyAlterEvent($policy, $this->createMock(Response::class));

    $subscriber->onCspPolicyAlter($event);

    $this->assertEquals("webrtc '$value'", $policy->getHeaderValue());
  }

}
