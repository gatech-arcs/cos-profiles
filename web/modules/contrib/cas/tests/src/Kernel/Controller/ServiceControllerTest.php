<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\cas\Controller\ServiceController;
use Drupal\cas\Event\CasPreUserLoadEvent;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Tests the service controller.
 *
 * @group cas
 * @coversDefaultClass \Drupal\cas\Controller\ServiceController
 */
class ServiceControllerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'cas_test',
    'externalauth',
    'http_request_mock',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['cas']);
    $this->config('cas.settings')->set('server.hostname', 'example.com')->save();
    $this->installSchema('cas', ['cas_login_data', 'cas_pgt_storage']);
    $this->installSchema('externalauth', ['authmap']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests a single logout request.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @covers \Drupal\cas\Service\CasLogout::handleSlo
   * @dataProvider providerTestCases
   */
  public function testSingleLogout(array $query): void {
    $db = $this->container->get('database');
    $db->insert('cas_login_data')
      ->fields(['ticket', 'plainsid', 'sid', 'created'])
      ->values(['ticket' => 'foo', 'plainsid' => 'bar', 'sid' => 'baz', 'created' => time()])
      ->execute();
    $this->config('cas.settings')->set('logout.enable_single_logout', TRUE)->save();

    $request = new Request($query, ['logoutRequest' => '<SessionIndex>foo</SessionIndex>']);
    $response = $this->getServiceController()->handle($request);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('', $response->getContent());

    $hasSession = (bool) $db->select('cas_login_data')
      ->fields('cas_login_data', ['ticket'])
      ->condition('ticket', 'foo')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertFalse($hasSession);
  }

  /**
   * Tests that we redirect to the homepage when no service ticket is present.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   *
   * @dataProvider providerTestCases
   */
  public function testMissingTicketRedirectsHome(array $query): void {
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);
    $this->assertTrue($response->isRedirect('/'));
  }

  /**
   * Tests that validation and logging in occurs when a ticket is present.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @covers \Drupal\cas\Service\CasValidator
   * @dataProvider providerTestCases
   */
  public function testSuccessfulLogin(array $query): void {
    $account = $this->createCasUser();
    $query += ['ticket' => 'ST-foobar'];
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);
    $this->assertTrue($response->isRedirect('/'));
    $this->assertUserIsLoggedIn($account);
  }

  /**
   * Tests that a user is validated and logged in with Drupal acting as proxy.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @covers \Drupal\cas\Service\CasValidator
   * @dataProvider providerTestCases
   */
  public function testSuccessfulLoginProxyEnabled(array $query): void {
    $this->config('cas.settings')->set('proxy.initialize', TRUE)->save();
    $account = $this->createCasUser();
    $query += ['ticket' => 'ST-foobar'];
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);
    $this->assertTrue($response->isRedirect('/'));
    $this->assertUserIsLoggedIn($account);
  }

  /**
   * Tests for a potential validation error.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @covers \Drupal\cas\Service\CasValidator
   * @dataProvider providerTestCases
   */
  public function testTicketValidationError(array $query): void {
    $account = $this->createCasUser();
    $query += ['ticket' => 'ST-invalid'];
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);
    $this->assertTrue($response->isRedirect('/user/login'));
    $this->assertUserIsNotLoggedIn($account);
  }

  /**
   * Tests for a potential login error.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @covers \Drupal\cas\Service\CasUserManager::login
   * @dataProvider providerTestCases
   */
  public function testLoginError(array $query): void {
    $blockedAccount = $this->createUser([], NULL, FALSE, ['status' => FALSE]);
    $query += ['ticket' => 'ST-foobar'];
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);

    $this->assertEquals('You do not have an account on this website. Please contact a site administrator.', $this->container->get('messenger')->messagesByType('error')[0]);
    $this->assertTrue($response->isRedirect('/user/login'));
    $this->assertUserIsNotLoggedIn($blockedAccount);
  }

  /**
   * An event listener alters username before attempting to load user.
   *
   * @param array $query
   *   The URL query string as associative array.
   *
   * @covers ::handle
   * @dataProvider providerTestCases
   */
  public function testEventListenerChangesCasUsername(array $query): void {
    $this->container->get('event_dispatcher')->addListener(
      CasPreUserLoadEvent::class,
      function (CasPreUserLoadEvent $event): void {
        $event->getCasPropertyBag()->setUsername('jane.roe');
      },
    );

    $account = $this->createCasUser();
    $query += ['ticket' => 'ST-foobar'];
    $request = new Request($query);
    $response = $this->getServiceController()->handle($request);
    $this->assertEquals('You do not have an account on this website. Please contact a site administrator.', $this->container->get('messenger')->messagesByType('error')[0]);
    $this->assertTrue($response->isRedirect('/user/login'));
    $this->assertUserIsNotLoggedIn($account);
  }

  /**
   * Provides test cases for kernel tests.
   *
   * @return array[]
   *   Test cases.
   *
   * @see testSingleLogout
   * @see testMissingTicketRedirectsHome
   * @see testSuccessfulLogin
   * @see testSuccessfulLoginProxyEnabled
   * @see testTicketValidationError
   * @see testLoginError
   * @see testEventListenerChangesCasUsername
   */
  public static function providerTestCases(): array {
    return [
      [[]],
      [['destination' => 'node/1']],
    ];
  }

  /**
   * Instantiates and returns the service controller.
   *
   * @return \Drupal\cas\Controller\ServiceController
   *   The service controller.
   */
  protected function getServiceController(): ServiceController {
    $classResolver = $this->container->get('class_resolver');
    return $classResolver->getInstanceFromDefinition(ServiceController::class);
  }

  /**
   * Asserts that a given user account is currently logged in as a CAS user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param string|null $casUserName
   *   (optional) The CAS username. Defaults to the Drupal account username.
   */
  protected function assertUserIsLoggedIn(UserInterface $account, ?string $casUserName = NULL): void {
    $session = $this->container->get('session');
    assert($session instanceof SessionInterface);
    $this->assertSame($account->id(), $session->get('uid'));
    $this->assertTrue($session->get('is_cas_user'));
    $casUserName ??= $account->getAccountName();
    $this->assertSame($casUserName, $session->get('cas_username'));
  }

  /**
   * Asserts that a given user account is not currently logged in.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   */
  protected function assertUserIsNotLoggedIn(UserInterface $account): void {
    $session = $this->container->get('session');
    assert($session instanceof SessionInterface);
    $this->assertNotSame($account->id(), $session->get('uid'));
  }

  /**
   * Creates a Drupal user account associated with CAS.
   *
   * @param string|null $casUserName
   *   (optional) The CAS username. Defaults to the Drupal account username.
   *
   * @return \Drupal\user\UserInterface
   *   The user account entity.
   */
  protected function createCasUser(?string $casUserName = NULL): UserInterface {
    $account = $this->createUser([], 'john.doe');
    $casUserName ??= $account->getAccountName();
    $this->container->get('externalauth.authmap')->save($account, 'cas', $casUserName);
    return $account;
  }

}
