<?php

declare(strict_types=1);

namespace Drupal\Tests\cas_attributes\Unit\Subscriber;

use Drupal\TestTools\Random;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas_attributes\Model\SyncFrequency;
use Drupal\cas_attributes\Subscriber\CasAttributesSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\cas_attributes\Subscriber\CasAttributesSubscriber
 * @group cas_attributes
 */
class CasAttributesSubscriberTest extends UnitTestCase {

  /**
   * The mocked UserInterface account.
   */
  protected UserInterface&MockObject $account;

  /**
   * The mocked token service.
   */
  protected Token&MockObject $tokenService;

  /**
   * The mocked CasPropertyBag.
   */
  protected CasPropertyBag&MockObject $propertyBag;

  /**
   * The mocked Request Stack.
   */
  protected RequestStack&MockObject $requestStack;

  /**
   * The mocked Session.
   */
  protected Session&MockObject $session;

  /**
   * An array for a Session attributes storage.
   */
  protected array $sessionData = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createMock('\Drupal\user\UserInterface');
    $this->tokenService = $this->getMockBuilder('\Drupal\Core\Utility\Token')
      ->disableOriginalConstructor()
      ->getMock();
    $this->propertyBag = $this->getMockBuilder('\Drupal\cas\CasPropertyBag')
      ->disableOriginalConstructor()
      ->getMock();
    $this->requestStack = $this->getMockBuilder('\Symfony\Component\HttpFoundation\RequestStack')
      ->disableOriginalConstructor()
      ->getMock();
    $this->session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Test the field mapping functionality for username.
   *
   * @dataProvider mapFieldsOnLogin
   */
  public function testMapFieldsOnLogin(array $mappings, array $attributes, bool $overwrite, bool $empty): void {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'field.mappings' => $mappings,
        'field.overwrite' => $overwrite,
        'field.sync_frequency' => SyncFrequency::EveryLogin->value,
      ],
    ]);

    $this
      ->propertyBag
      ->expects($this->any())
      ->method('getAttributes')
      ->willReturn($attributes);

    $this
      ->tokenService
      ->expects($this->any())
      ->method('replace')
      ->willReturnCallback([$this, 'tokenReplace']);

    if ($empty || $overwrite) {
      $this
        ->account
        ->expects($this->once())
        ->method('set')
        ->with('name', $attributes[$mappings['name']][0]);
    }
    else {
      $this
        ->account
        ->expects($this->never())
        ->method('setUsername');
    }

    $event = new CasPreLoginEvent($this->account, $this->propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreLogin($event);
  }

  /**
   * Provide parameters for testMapFieldsOnLogin.
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas_attributes\Unit\Subscriber\CasAttributesSubscriberTest::testMapFieldsOnLogin
   */
  public static function mapFieldsOnLogin(): array {
    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [Random::machineName()]],
      TRUE,
      FALSE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [Random::machineName()]],
      FALSE,
      FALSE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [Random::machineName()]],
      FALSE,
      TRUE,
    ];

    $params[] = [
      ['name' => 'usernameAttribute'],
      ['usernameAttribute' => [Random::machineName()]],
      TRUE,
      TRUE,
    ];

    return $params;
  }

  /**
   * Callback function for the mocked token replacement service.
   *
   * @param string $input
   *   The string containing a token.
   * @param array $data
   *   Data for the token replacement.
   *
   * @return string
   *   The token replacement.
   */
  public function tokenReplace(string $input, array $data): string {
    // We don't particularly care about token replacement logic in this test,
    // only that it happens we want it to. So for the purposes of this test,
    // we use very simple fake token syntax.
    $supplied_attribute = preg_replace('/[\[\]]/', '', $input);
    if (isset($data['cas_attributes']) && is_array($data['cas_attributes'])) {
      if (isset($data['cas_attributes'][$supplied_attribute])) {
        return $data['cas_attributes'][$supplied_attribute][0];
      }
    }

    // No match, just return the input.
    return $input;
  }

  /**
   * Verifies the 'deny registration feature' when no roles map to user.
   */
  public function testDenyRegistrationOnNoRoleMatch(): void {
    // Set up a role/attr mapping config and configure CAS Attributes to DENY
    // registration when no role/attr mapping can be established for a user.
    $roleMapping = [
      [
        'rid' => $this->randomMachineName(8),
        'method' => 'exact_any',
        'attribute' => 'fruit',
        'value' => 'apple',
        'remove_without_match' => FALSE,
      ],
    ];
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.sync_frequency' => SyncFrequency::EveryLogin->value,
        'role.deny_registration_no_match' => TRUE,
        'role.mappings' => $roleMapping,
      ],
    ]);

    // Give the user an attribute that does not match our role mapping.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['orange']);

    // Now call the preRegister method and confirm that the user would be
    // denied registration.
    $preRegisterEvent = new CasPreRegisterEvent($propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($preRegisterEvent);
    $this->assertFalse($preRegisterEvent->getAllowAutomaticRegistration());

    // Give the user an attribute that maps to one of the roles, and confirm
    // they are no longer denied.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['apple']);

    $preRegisterEvent = new CasPreRegisterEvent($propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($preRegisterEvent);
    $this->assertTrue($preRegisterEvent->getAllowAutomaticRegistration());

    // Update configuration so that registration will not be denied when no role
    // mapping match exists.
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.deny_registration_no_match' => FALSE,
        'role.role_mapping' => $roleMapping,
      ],
    ]);

    // Give a user an incorrect attribute value, and confirm they can still
    // register.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['orange']);
    $preRegisterEvent = new CasPreRegisterEvent($propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($preRegisterEvent);
    $this->assertTrue($preRegisterEvent->getAllowAutomaticRegistration());
  }

  /**
   * Verifies the 'deny login feature' when no roles map to user.
   */
  public function testDenyLoginOnNoRoleMatch(): void {
    // Set up a role/attr mapping config and configure CAS Attributes to DENY
    // login when no role/attr mapping can be established for a user.
    $roleMapping = [
      [
        'rid' => $this->randomMachineName(8),
        'method' => 'exact_any',
        'attribute' => 'fruit',
        'value' => 'apple',
        'remove_without_match' => FALSE,
      ],
    ];
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.sync_frequency' => SyncFrequency::EveryLogin->value,
        'role.deny_login_no_match' => TRUE,
        'role.mappings' => $roleMapping,
      ],
    ]);

    // Give the user an attribute that does not match our role mapping.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['orange']);

    // Now call the preRegister method and confirm that the user would be
    // denied login.
    $preLoginEvent = new CasPreLoginEvent($this->account, $propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreLogin($preLoginEvent);
    $this->assertFalse($preLoginEvent->getAllowLogin());

    // Give the user an attribute that maps to one of the roles, and confirm
    // they are no longer denied.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['apple']);

    $preLoginEvent = new CasPreLoginEvent($this->account, $propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreLogin($preLoginEvent);
    $this->assertTrue($preLoginEvent->getAllowLogin());

    // Update configuration so that login will not be denied when no role
    // mapping match exists.
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.sync_frequency' => SyncFrequency::EveryLogin->value,
        'role.deny_login_no_match' => FALSE,
        'role.mappings' => $roleMapping,
      ],
    ]);

    // Give a user an incorrect attribute value, and confirm they can still
    // log in.
    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttribute('fruit', ['orange']);
    $preLoginEvent = new CasPreLoginEvent($this->account, $propertyBag);
    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreLogin($preLoginEvent);
    $this->assertTrue($preLoginEvent->getAllowLogin());
  }

  /**
   * Tests role mapping comparison methods work as expected on registration.
   *
   * @dataProvider roleMappingComparisonMethodsDataProvider
   */
  public function testRoleMappingComparisonMethodsOnRegistration(array $scenarioData): void {
    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.sync_frequency' => SyncFrequency::EveryLogin->value,
        'role.mappings' => $scenarioData['mappings'],
      ],
    ]);

    $this->propertyBag
      ->method('getAttributes')
      ->willReturn($scenarioData['attributes_user_has']);

    $event = new CasPreRegisterEvent($this->propertyBag);
    $event->setPropertyValue('roles', $scenarioData['roles_before']);

    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $subscriber->onPreRegister($event);

    $this->assertEquals($scenarioData['roles_after'], $event->getPropertyValues()['roles']);
  }

  /**
   * Provides data for testing role mapping scenarios.
   *
   * @return array
   *   Parameters.
   */
  public static function roleMappingComparisonMethodsDataProvider(): array {
    $scenarios = [];

    // Role B should be added because the user has the exact attribute we're
    // looking for.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA', 'roleB'],
        'attributes_user_has' => ['attrA' => ['bananas']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];
    // Same as above, but test that different cases for attribute names makes
    // no difference.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA', 'roleB'],
        'attributes_user_has' => ['attra' => ['bananas']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA', 'roleB'],
        'attributes_user_has' => ['attrA' => ['bananas']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attra',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // Role B will NOT be added because the value we're looking for is not an
    // exact match with that the user has.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA'],
        'attributes_user_has' => ['attrA' => ['bananas']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'banan',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // Here we make sure that two role mappings that each add different roles
    // will work as expected.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA', 'roleB', 'roleC'],
        'attributes_user_has' => ['attrA' => ['bananas'], 'attrB' => ['cucumbers']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
          [
            'rid' => 'roleC',
            'method' => 'exact_single',
            'attribute' => 'attrB',
            'value' => 'cucumbers',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // Role B should not be added, because even though the attribute value
    // we're checking exists, it's part of a multi-value array, and the
    // method only works if it's a single value array.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA'],
        'attributes_user_has' => ['attrA' => ['bananas', 'cucumbers']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // However if we switch the method to variation that searches all items
    // in the attribute value array, it should work.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleA', 'roleB'],
        'attributes_user_has' => ['attrA' => ['bananas', 'cucumbers']],
        'mappings' => [
          [
            'rid' => 'roleB',
            'method' => 'exact_any',
            'attribute' => 'attrA',
            'value' => 'bananas',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // Role A should be REMOVED from the user, because it's mapping fails.
    $scenarios[] = [
      [
        'roles_before' => ['roleA'],
        'roles_after' => ['roleB'],
        'attributes_user_has' => ['attrA' => ['bananas'], 'attrB' => ['cucumbers']],
        'mappings' => [
          [
            'rid' => 'roleA',
            'method' => 'exact_single',
            'attribute' => 'attrA',
            'value' => 'cherries',
            'remove_without_match' => TRUE,
          ],
          [
            'rid' => 'roleB',
            'method' => 'exact_single',
            'attribute' => 'attrB',
            'value' => 'cucumbers',
            'remove_without_match' => FALSE,
          ],
        ],
      ],
    ];

    // Test that the 'contains' method works as expected. Student role should
    // be added because the value to check appears as a substring in the actual
    // value.
    $scenarios[] = [
      [
        'roles_before' => [],
        'roles_after' => ['student'],
        'attributes_user_has' => ['groups' => ['Linux User', 'First Year Student']],
        'mappings' => [
          [
            'rid' => 'student',
            'method' => 'contains_any',
            'attribute' => 'groups',
            'value' => 'Student',
            'remove_without_match' => TRUE,
          ],
        ],
      ],
    ];

    // Test that the 'regex' method works as expected. Student role should be
    // added because the regex checking for a value passes.
    $scenarios[] = [
      [
        'roles_before' => [],
        'roles_after' => ['student'],
        'attributes_user_has' => ['groups' => ['Linux User', 'First Year Student']],
        'mappings' => [
          [
            'rid' => 'student',
            'method' => 'regex_any',
            'attribute' => 'groups',
            'value' => '/.+student$/i',
            'remove_without_match' => TRUE,
          ],
        ],
      ],
    ];

    // Test that the 'negate' flag works.
    $scenarios[] = [
      [
        'roles_before' => [],
        'roles_after' => ['undergrad'],
        'attributes_user_has' => ['groups' => ['Linux User', 'First Year Student']],
        'mappings' => [
          [
            'rid' => 'undergrad',
            'method' => 'contains_any',
            'attribute' => 'groups',
            'value' => 'Graduate Student',
            'negate' => TRUE,
            'remove_without_match' => TRUE,
          ],
        ],
      ],
      [
        'roles_before' => [],
        'roles_after' => [],
        'attributes_user_has' => ['groups' => ['Linux User', 'Graduate Student']],
        'mappings' => [
          [
            'rid' => 'undergrad',
            'method' => 'contains_any',
            'attribute' => 'groups',
            'value' => 'Graduate Student',
            'negate' => TRUE,
            'remove_without_match' => TRUE,
          ],
        ],
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests that role mapping works as expected during login.
   */
  public function testRoleMappingOnLogin(): void {
    $role_map = [
      [
        'rid' => 'bananarole',
        'method' => 'exact_single',
        'attribute' => 'fruit',
        'value' => 'banana',
        'remove_without_match' => FALSE,
      ],
      [
        'rid' => 'orangerole',
        'method' => 'exact_single',
        'attribute' => 'FRUIT',
        'value' => 'orange',
        'remove_without_match' => FALSE,
      ],
    ];

    $config_factory = $this->getConfigFactoryStub([
      'cas_attributes.settings' => [
        'role.sync_frequency' => SyncFrequency::EveryLogin->value,
        'role.mappings' => $role_map,
      ],
    ]);

    // Just one role should be added to the user, since the other
    // doesn't have an attribute match.
    $account = $this->createMock('\Drupal\user\UserInterface');
    $account->expects($this->once())
      ->method('addRole')
      ->with('bananarole');

    $propertyBag = new CasPropertyBag('test');
    // Intentional set a different case than the role mapping attribute name
    // to ensure it doesn't matter and a match occurs either way.
    $propertyBag->setAttribute('FRUIT', ['banana']);

    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $event = new CasPreLoginEvent($account, $propertyBag);
    $subscriber->onPreLogin($event);
  }

  /**
   * Tests that role mapping works as expected during login.
   *
   * @dataProvider allowedAttributesSettings
   */
  public function testAllowedAttributesSettings(array $settings, array $cas_attributes, array $expected_attributes): void {
    $config_factory = $this->getConfigFactoryStub($settings);

    $request = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();

    $request
      ->expects($this->any())
      ->method('getSession')
      ->willReturn($this->session);

    $this
      ->requestStack
      ->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this
      ->session
      ->expects($this->any())
      ->method('set')
      ->willReturnCallback([$this, 'setSessionAttr']);

    $this
      ->session
      ->expects($this->any())
      ->method('get')
      ->willReturnCallback([$this, 'getSessionAttr']);

    $account = $this->createMock('\Drupal\user\UserInterface');

    $propertyBag = new CasPropertyBag('test');
    $propertyBag->setAttributes($cas_attributes);

    $subscriber = new CasAttributesSubscriber($config_factory, $this->tokenService, $this->requestStack);
    $event = new CasPostLoginEvent($account, $propertyBag);
    $subscriber->onPostLogin($event);

    $session = $this->requestStack->getCurrentRequest()->getSession();
    $attributes = $session->get('cas_attributes');

    $this->assertEquals($attributes, $expected_attributes);

  }

  /**
   * Provide parameters for testAllowedAttributesSettings.
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas_attributes\Unit\Subscriber\CasAttributesSubscriberTest::testAllowedAttributesSettings
   */
  public static function allowedAttributesSettings(): array {

    $scenarios = [
      'Not allowed attributes are filtered out' => [
        [
          'cas_attributes.settings' => [
            'sitewide_token_support' => TRUE,
            'token_allowed_attributes' => [
              'id',
              'name',
              'EMAIL',
            ],
          ],
        ],
        [
          'id' => ['user_id'],
          // Intentionally set a different case than the allowed attribute name
          // to ensure it doesn't matter and a match occurs either way.
          'NAME' => ['user_name'],
          'email' => ['user@example.com'],
          'not_allowed' => ['not_allowed'],
          'one_more_not_allowed' => ['not_allowed'],
        ],
        [
          'id' => ['user_id'],
          'NAME' => ['user_name'],
          'email' => ['user@example.com'],
        ],
      ],
      'Allowed more attributes than existed' => [
        [
          'cas_attributes.settings' => [
            'sitewide_token_support' => TRUE,
            'token_allowed_attributes' => [
              'id',
              'name',
              'email',
            ],
          ],
        ],
        [
          'id' => ['user_id'],
          'name' => ['user_name'],
        ],
        [
          'id' => ['user_id'],
          'name' => ['user_name'],
        ],
      ],
    ];

    return $scenarios;
  }

  /**
   * Callback for the setting the attribute of the mocked session service.
   *
   * @param string $name
   *   Session name.
   * @param mixed $value
   *   Session value.
   */
  public function setSessionAttr(string $name, mixed $value): void {
    $this->sessionData[$name] = $value;
  }

  /**
   * Callback for the getting the attribute of the mocked session service.
   *
   * @param string $name
   *   The attribute name.
   * @param mixed $default
   *   The default value if not found.
   *
   * @return mixed
   *   Session attribute.
   */
  public function getSessionAttr(string $name, mixed $default = NULL): mixed {
    if (isset($this->sessionData[$name])) {
      return $this->sessionData[$name];
    }
    return $default;
  }

}
