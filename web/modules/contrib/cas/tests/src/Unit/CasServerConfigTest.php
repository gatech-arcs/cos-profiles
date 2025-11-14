<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasServerConfig;
use Drupal\cas\Model\CasProtocolVersion;
use Drupal\cas\Model\HttpScheme;
use Drupal\cas\Model\SslCertificateVerification;

/**
 * CasServerConfig unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 */
class CasServerConfigTest extends UnitTestCase {

  /**
   * Test getters.
   */
  public function testGetters(): void {
    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.protocol' => 'https',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '1.0',
        'server.verify' => SslCertificateVerification::Default->value,
        'server.cert' => 'foo',
        'advanced.connection_timeout' => 30,
      ],
    ]);

    $serverConfig = CasServerConfig::createFromModuleConfig($configFactory->get('cas.settings'));

    $this->assertEquals('example.com', $serverConfig->getHostname());
    $this->assertEquals(HttpScheme::Https, $serverConfig->getHttpScheme());
    $this->assertEquals(443, $serverConfig->getPort());
    $this->assertEquals('/cas', $serverConfig->getPath());
    $this->assertEquals(CasProtocolVersion::Version1, $serverConfig->getProtocolVersion());
    $this->assertEquals('foo', $serverConfig->getCustomRootCertBundlePath());
    $this->assertEquals(SslCertificateVerification::Default, $serverConfig->getVerify());
    $this->assertEquals(30, $serverConfig->getDirectConnectionTimeout());
  }

  /**
   * Test getCasServerGuzzleConnectionOptions.
   *
   * @dataProvider casServerConnectionOptionsDataProvider
   */
  public function testCasServerGuzzleConnectionOptions(SslCertificateVerification $sslVerifyMethod): void {
    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.protocol' => 'https',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '1.0',
        'server.verify' => $sslVerifyMethod->value,
        'server.cert' => 'foo',
        'advanced.connection_timeout' => 30,
      ],
    ]);

    $serverConfig = CasServerConfig::createFromModuleConfig($configFactory->get('cas.settings'));

    switch ($sslVerifyMethod) {
      case SslCertificateVerification::Custom:
        $this->assertEquals(['verify' => 'foo', 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;

      case SslCertificateVerification::None:
        $this->assertEquals(['verify' => FALSE, 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;

      default:
        $this->assertEquals(['verify' => TRUE, 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;
    }
  }

  /**
   * Data provider for testCasServerGuzzleConnectionOptions.
   *
   * @return array
   *   The data to provide.
   */
  public static function casServerConnectionOptionsDataProvider(): array {
    return array_map(fn(SslCertificateVerification $case) => [$case], SslCertificateVerification::cases());
  }

  /**
   * Test getServerBaseUrl.
   *
   * @dataProvider getServerBaseUrlDataProvider
   */
  public function testGetServerBaseUrl(array $serverConfig, string $expectedBaseUrl): void {
    $serverConfig += [
      'server.version' => '2.0',
      'server.verify' => 0,
      'server.cert' => '',
      'advanced.connection_timeout' => 10,
    ];
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => $serverConfig,
    ]);

    $casServerConfig = CasServerConfig::createFromModuleConfig($config_factory->get('cas.settings'));

    $this->assertEquals($expectedBaseUrl, $casServerConfig->getServerBaseUrl());
  }

  /**
   * Data provider for testGetServerBaseUrl.
   */
  public static function getServerBaseUrlDataProvider(): array {
    return [
      [
        [
          'server.protocol' => 'https',
          'server.hostname' => 'example.com',
          'server.port' => 443,
          'server.path' => '/cas',
        ],
        'https://example.com/cas/',
      ],
      [
        [
          'server.protocol' => 'http',
          'server.hostname' => 'foobar.net',
          'server.port' => 4433,
          'server.path' => '/cas-alt',
        ],
        'http://foobar.net:4433/cas-alt/',
      ],
    ];
  }

}
