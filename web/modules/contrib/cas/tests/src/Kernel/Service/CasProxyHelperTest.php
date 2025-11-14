<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;

/**
 * CasHelper unit tests.
 *
 * @group cas
 * @coversDefaultClass \Drupal\cas\Service\CasProxyHelper
 */
class CasProxyHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'cas_test',
    'externalauth',
    'http_request_mock',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['cas']);
    $this->config('cas.settings')->set('server.hostname', 'example.com')->save();
  }

  /**
   * Test proxy authentication to a service.
   *
   * @covers ::proxyAuthenticate
   * @covers ::getServerProxyURL
   * @covers ::parseProxyTicket
   */
  public function testProxyAuthenticate(): void {
    $this->container->get('config.factory')->getEditable('cas.settings')
      ->set('proxy.initialize', TRUE)
      ->save();
    $targetService = 'https://example.com/service';
    $cookieDomain = 'example.com';
    $session = $this->container->get('session');

    // Set up properties so the HTTP client callback knows about them.
    $cookieValue = 'foobar';

    // Set up the fake pgt in the session.
    $session->set('cas_pgt', $this->randomMachineName(24));

    // A service that has already been proxied.
    $sessionCasProxyHelper[$targetService][] = [
      'Name' => 'SESSION',
      'Value' => $cookieValue,
      'Domain' => $cookieDomain,
    ];
    $session->set('cas_proxy_helper', $sessionCasProxyHelper);

    $jar = $this->container->get('cas.proxy_helper')->proxyAuthenticate($targetService);
    $cookie = $jar->toArray();
    $this->assertEquals('SESSION', $cookie[0]['Name']);
    $this->assertEquals($cookieValue, $cookie[0]['Value']);
    $this->assertEquals($cookieDomain, $cookie[0]['Domain']);

    // Proxying a new service that was not previously proxied.
    $jar = $this->container->get('cas.proxy_helper')->proxyAuthenticate($targetService);
    $this->assertEquals('SESSION', $session->get('cas_proxy_helper')[$targetService][0]['Name']);
    $this->assertEquals($cookieValue, $session->get('cas_proxy_helper')[$targetService][0]['Value']);
    $this->assertEquals($cookieDomain, $session->get('cas_proxy_helper')[$targetService][0]['Domain']);
    $cookie = $jar->toArray();
    $this->assertEquals('SESSION', $cookie[0]['Name']);
    $this->assertEquals($cookieValue, $cookie[0]['Value']);
    $this->assertEquals($cookieDomain, $cookie[0]['Domain']);
  }

}
