<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\TestTools\Random;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasProxyHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasProxyHelper
 */
class CasProxyHelperTest extends UnitTestCase {

  /**
   * The mocked session manager.
   */
  protected SessionInterface $session;

  /**
   * The mocked CAS helper.
   */
  protected CasHelper|MockObject $casHelper;

  /**
   * The mocked database connection object.
   */
  protected Connection|MockObject $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->session = new Session(new MockArraySessionStorage());
    $this->session->start();
    $this->casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');
    $this->database = $this->createMock('\Drupal\Core\Database\Connection');
  }

  /**
   * Test the possible exceptions from proxy authentication.
   *
   * @param bool $is_proxy
   *   expected isProxy method return value.
   * @param bool $is_pgt_set
   *   Whether the cas_pgt session parameter is set.
   * @param string $target_service
   *   Target service.
   * @param string $response
   *   Expected response data.
   * @param string|false $client_exception
   *   Expected exception data or FALSE.
   * @param string $exception_type
   *   Expected exception type.
   * @param string $exception_message
   *   Expected exception message.
   *
   * @covers ::proxyAuthenticate
   * @covers ::getServerProxyURL
   * @covers ::parseProxyTicket
   *
   * @dataProvider proxyAuthenticateExceptionDataProvider
   */
  public function testProxyAuthenticateException(bool $is_proxy, bool $is_pgt_set, string $target_service, string $response, string|false $client_exception, string $exception_type, string $exception_message): void {
    if ($is_pgt_set) {
      // Set up the fake pgt in the session.
      $this->session->set('cas_pgt', $this->randomMachineName(24));
    }
    // Set up properties so the http client callback knows about them.
    $cookie_value = $this->randomMachineName(24);

    if ($client_exception == 'server') {
      $code = 404;
    }
    else {
      $code = 200;
    }
    if ($client_exception == 'client') {
      $secondResponse = new Response(404);
    }
    else {
      $secondResponse = new Response(200, [
        'Content-type' => 'text/html',
        'Set-Cookie' => 'SESSION=' . $cookie_value,
      ]);
    }
    $mock = new MockHandler(
      [new Response($code, [], $response), $secondResponse]
    );
    $handler = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handler]);

    $casProxyHelper = new CasProxyHelper($httpClient, $this->casHelper, $this->session, $this->database);
    $this->expectException($exception_type, $exception_message);
    $casProxyHelper->proxyAuthenticate($target_service);

  }

  /**
   * Provides parameters and exceptions for testProxyAuthenticateException.
   *
   * @return array
   *   Parameters and exceptions.
   *
   * @see \Drupal\Tests\cas\Unit\Service\CasProxyHelperTest::testProxyAuthenticateException
   */
  public static function proxyAuthenticateExceptionDataProvider(): array {
    $target_service = 'https://example.com';
    $exception_type = '\Drupal\cas\Exception\CasProxyException';
    // Exception case 1: not configured as proxy.
    $params[] = [
      FALSE,
      TRUE,
      $target_service,
      '',
      FALSE,
      $exception_type,
      'Session state not sufficient for proxying.',
    ];

    // Exception case 2: session pgt not set.
    $params[] = [
      TRUE,
      FALSE,
      $target_service,
      '',
      FALSE,
      $exception_type,
      'Session state not sufficient for proxying.',
    ];

    // Exception case 3: http client exception from proxy app.
    $proxy_ticket = Random::machineName(24);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
          <cas:proxyTicket>PT-$proxy_ticket</cas:proxyTicket>
        </cas:proxySuccess>
      </cas:serviceResponse>";

    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      'client',
      $exception_type,
      '',
    ];

    // Exception case 4: http client exception from CAS Server.
    $proxy_ticket = Random::machineName(24);
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
          <cas:proxyTicket>PT-$proxy_ticket</cas:proxyTicket>
        </cas:proxySuccess>
      </cas:serviceResponse>";

    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      'server',
      $exception_type,
      '',
    ];

    // Exception case 5: non-XML response from CAS server.
    $response = "<> </> </ <..";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server returned non-XML response.',
    ];

    // Exception case 6: CAS Server rejected ticket.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:proxyFailure code=\"INVALID_REQUEST\">
           'pgt' and 'targetService' parameters are both required
         </cas:proxyFailure>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server rejected proxy request.',
    ];

    // Exception case 7: Neither proxyFailure nor proxySuccess specified.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
         <cas:proxy code=\"INVALID_REQUEST\">
         </cas:proxy>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server returned malformed response.',
    ];

    // Exception case 8: Malformed ticket.
    $response = "<cas:serviceResponse xmlns:cas='http://example.com/cas'>
        <cas:proxySuccess>
        </cas:proxySuccess>
       </cas:serviceResponse>";
    $params[] = [
      TRUE,
      TRUE,
      $target_service,
      $response,
      FALSE,
      $exception_type,
      'CAS Server provided invalid or malformed ticket.',
    ];

    return $params;
  }

}
