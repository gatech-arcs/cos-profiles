<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Model\EmailAssignment;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasProxyHelper;
use Drupal\cas\Service\CasUserManager;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * CasUserManager unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasUserManager
 */
class CasUserManagerTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * The mocked External Auth manager.
   */
  protected ExternalAuthInterface $externalAuth;

  /**
   * The mocked Authmap.
   */
  protected AuthmapInterface $authmap;

  /**
   * The mocked Entity Manager.
   */
  protected EntityTypeManagerInterface|MockObject $entityManager;

  /**
   * The mocked session manager.
   */
  protected SessionInterface $session;

  /**
   * The mocked database connection.
   */
  protected Connection|MockObject $connection;

  /**
   * The mocked event dispatcher.
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The mocked user manager.
   */
  protected CasUserManager $userManager;

  /**
   * The mocked Cas Helper service.
   */
  protected CasHelper $casHelper;

  /**
   * The CAS proxy helper.
   */
  protected ObjectProphecy $casProxyHelper;

  /**
   * The mocked user account.
   */
  protected UserInterface $account;

  /**
   * The mocked password generator service.
   */
  protected PasswordGeneratorInterface|ObjectProphecy $passwordGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->externalAuth = $this->createMock('\Drupal\externalauth\ExternalAuth');
    $this->authmap = $this->createMock('\Drupal\externalauth\Authmap');
    $storage = $this->createMock('\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage');
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->setConstructorArgs([$storage])
      ->getMock();
    $this->session->start();
    $this->connection = $this->createMock('\Drupal\Core\Database\Connection');
    $this->eventDispatcher = $this->createMock('\Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $this->casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');
    $this->account = $this->createMock('Drupal\user\UserInterface');
    $this->casProxyHelper = $this->prophesize(CasProxyHelper::class);
    $this->passwordGenerator = $this->prophesize(PasswordGeneratorInterface::class);
  }

  /**
   * Basic scenario that user is registered.
   *
   * Create new account for a user.
   *
   * @covers ::register
   */
  public function testUserRegister(): void {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_assigned_roles' => [],
      ],
      'user.settings' => [
        'register' => 'visitors',
      ],
    ]);

    $this->externalAuth
      ->method('register')
      ->willReturn($this->account);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['randomPassword'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->assertNotEmpty($cas_user_manager->register('test', 'test', []), 'Successfully registered user.');
  }

  /**
   * User account doesn't exist but auto registration is disabled.
   *
   * An exception should be thrown and the user should not be logged in.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndAutoRegistrationDisabled(): void {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'user_accounts.auto_register' => FALSE,
      ],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData', 'register'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $cas_user_manager
      ->expects($this->never())
      ->method('register');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'Cannot login, local Drupal user account does not exist.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * User account doesn't exist, auto reg is enabled, but listener denies.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndEventListenerDeniesAutoRegistration(): void {
    $this->casHelper->method('getCasSetting')->willReturnMap([
      ['user_accounts.auto_register', TRUE],
      ['user_accounts.email_assignment_strategy', EmailAssignment::Standard->value],
      ['user_accounts.email_hostname', 'sample.com'],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData', 'register'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub(),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event, $event_type) {
        if ($event instanceof CasPreRegisterEvent) {
          $event->cancelAutomaticRegistration();
        }
        return $event;
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('register');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException');
    $this->expectExceptionMessage("Registration of user 'test' denied by an event listener.");

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * Account doesn't exist, auto-register is on, listener allows registration.
   *
   * @covers ::login
   */
  public function testUserNotFoundAndEventListenerAllowAutoRegistration(): void {
    $config_factory = $this->getConfigFactoryStub([
      'user.settings' => [
        'register' => 'visitors',
      ],
    ]);
    $this->casHelper->method('getCasSetting')->willReturnMap([
      ['user_accounts.auto_register', TRUE],
      ['user_accounts.email_assignment_strategy', EmailAssignment::Standard->value],
      ['user_accounts.email_hostname', 'sample.com'],
      ['user_accounts.email_attribute', 'email'],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData', 'randomPassword'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $expected_assigned_email = 'test@sample.com';

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->account
      ->method('isActive')
      ->willReturn(TRUE);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event, $event_type) {
        if ($event instanceof CasPreRegisterEvent) {
          $event->allowAutomaticRegistration();
        }
        return $event;
      });

    $this->externalAuth
      ->expects($this->once())
      ->method('register')
      ->with('test', 'cas', [
        'name' => 'test',
        'mail' => $expected_assigned_email,
        'pass' => NULL,
        'status' => TRUE,
      ])
      ->willReturn($this->account);

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($this->account);

    $cas_property_bag = new CasPropertyBag('test');
    $cas_property_bag->setAttributes(['email' => 'test@sample.com']);

    $cas_user_manager->login($cas_property_bag, 'ticket');
  }

  /**
   * User account doesn't exist but is auto-registered and logged in.
   *
   * @dataProvider automaticRegistrationDataProvider
   *
   * @covers ::login
   */
  public function testAutomaticRegistration(EmailAssignment $email_assignment_strategy): void {
    $config_factory = $this->getConfigFactoryStub([
      'user.settings' => [
        'register' => 'visitors',
      ],
    ]);
    $this->casHelper->method('getCasSetting')->willReturnMap([
      ['user_accounts.auto_register', TRUE],
      ['user_accounts.email_assignment_strategy', $email_assignment_strategy->value],
      ['user_accounts.email_hostname', 'sample.com'],
      ['user_accounts.email_attribute', 'email'],
    ]);

    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData', 'randomPassword'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $config_factory,
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->externalAuth
      ->method('load')
      ->willReturn(FALSE);

    $this->account
      ->method('isActive')
      ->willReturn(TRUE);

    // The email address assigned to the user differs depending on the settings.
    // If CAS is configured to use "standard" assignment, it should combine the
    // username with the specified email hostname. If it's configured to use
    // "attribute" assignment, it should use the value of the specified CAS
    // attribute.
    $expected_assigned_email = match($email_assignment_strategy) {
      EmailAssignment::Standard => 'test@sample.com',
      EmailAssignment::FromAttribute => 'test_email@foo.com',
    };

    $this->externalAuth
      ->expects($this->once())
      ->method('register')
      ->with('test', 'cas', [
        'name' => 'test',
        'mail' => $expected_assigned_email,
        'pass' => NULL,
        'status' => TRUE,
      ])
      ->willReturn($this->account);

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($this->account);

    $cas_property_bag = new CasPropertyBag('test');
    $cas_property_bag->setAttributes(['email' => 'test_email@foo.com']);

    $cas_user_manager->login($cas_property_bag, 'ticket');
  }

  /**
   * A data provider for testing automatic user registration.
   *
   * @return array
   *   The two different email assignment strategies.
   */
  public static function automaticRegistrationDataProvider(): array {
    return array_map(
      fn(EmailAssignment $assignment): array => [$assignment],
      EmailAssignment::cases(),
    );
  }

  /**
   * An event listener prevents the user from logging in.
   *
   * @covers ::login
   */
  public function testEventListenerPreventsLogin(): void {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub([
          'cas.settings' => [
            'user_accounts' => [
              'auto_register_follow_registration_policy' => FALSE,
            ],
          ],
          'user.settings' => [
            'register' => 'visitors',
          ],
        ]),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isActive')
      ->willReturn(TRUE);

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $this->eventDispatcher
      ->method('dispatch')
      ->willReturnCallback(function ($event, $event_type) {
        if ($event instanceof CasPreLoginEvent) {
          $event->cancelLogin();
        }
        return $event;
      });

    $cas_user_manager
      ->expects($this->never())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'Cannot login, an event listener denied access.');

    $cas_user_manager->login(new CasPropertyBag('test'), 'ticket');
  }

  /**
   * A user is able to login when their account exists.
   *
   * @covers ::login
   */
  public function testExistingAccountIsLoggedIn(): void {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub([
          'cas.settings' => [
            'user_accounts' => [
              'auto_register_follow_registration_policy' => FALSE,
            ],
          ],
          'user.settings' => [
            'register' => 'visitors',
          ],
        ]),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isActive')
      ->willReturn(TRUE);

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $cas_user_manager
      ->expects($this->once())
      ->method('storeLoginSessionData');

    $this->externalAuth
      ->expects($this->once())
      ->method('userLoginFinalize')
      ->willReturn($this->account);

    $attributes = ['attr1' => 'foo', 'attr2' => 'bar'];
    $this->session
      ->method('set')
      ->willReturnOnConsecutiveCalls(
        ['is_cas_user', TRUE],
        ['cas_username', 'test']
      );

    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttributes($attributes);

    $cas_user_manager->login($propertyBag, 'ticket');
  }

  /**
   * Blockers users cannot log in.
   *
   * @covers ::login
   */
  public function testBlockedAccountIsNotLoggedIn(): void {
    $cas_user_manager = $this->getMockBuilder('Drupal\cas\Service\CasUserManager')
      ->onlyMethods(['storeLoginSessionData'])
      ->setConstructorArgs([
        $this->externalAuth,
        $this->authmap,
        $this->getConfigFactoryStub([
          'cas.settings' => [
            'user_accounts' => [
              'auto_register_follow_registration_policy' => FALSE,
            ],
          ],
          'user.settings' => [
            'register' => 'visitors',
          ],
        ]),
        $this->session,
        $this->connection,
        $this->eventDispatcher,
        $this->casHelper,
        $this->casProxyHelper->reveal(),
        $this->passwordGenerator->reveal(),
      ])
      ->getMock();

    $this->account
      ->method('isBlocked')
      ->willReturn(TRUE);
    $this->account
      ->method('getAccountName')
      ->willReturn('user');

    $this->externalAuth
      ->method('load')
      ->willReturn($this->account);

    $this->externalAuth
      ->expects($this->never())
      ->method('userLoginFinalize');

    $this->expectException('Drupal\cas\Exception\CasLoginException', 'The username user has not been activated or is blocked.');

    $this->session
      ->method('set')
      ->willReturnOnConsecutiveCalls(
          ['is_cas_user', TRUE],
          ['cas_username', 'test']
      );

    $propertyBag = new CasPropertyBag('test');
    $cas_user_manager->login($propertyBag, 'ticket');
  }

}
