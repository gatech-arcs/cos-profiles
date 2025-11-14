<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Drupal\cas\DependencyInjection\DeprecatedEventConstants;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Deprecation tests.
 *
 * @group cas
 * @group legacy
 */
class DeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cas', 'externalauth'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $definition = $container->getDefinition('event_dispatcher');
    foreach (DeprecatedEventConstants::DEPRECATE_EVENT_IDS as $constantName => $className) {
      $definition->addMethodCall('addListener', [
        constant($constantName),
        function () use ($className): void {
          // Leave a clue that this subscriber has been called.
          $this->container->get('state')->set('cas.test_event_constant.' . $className, TRUE);
        },
      ]);
    }

    // Register a memory logger to assert that a warning has been logged.
    $container->register('cas_test.logger', BufferingLogger::class)->addTag('logger');

    parent::register($container);
  }

  /**
   * @covers \Drupal\cas\DependencyInjection\DeprecatedEventConstants
   */
  public function testDeprecatedEventConstants(): void {
    $message = 'The following event constants are deprecated: Drupal\cas\Service\CasHelper::EVENT_PRE_USER_LOAD, Drupal\cas\Service\CasHelper::EVENT_PRE_USER_LOAD_REDIRECT, Drupal\cas\Service\CasHelper::EVENT_PRE_REGISTER, Drupal\cas\Service\CasHelper::EVENT_PRE_LOGIN, Drupal\cas\Service\CasHelper::EVENT_PRE_REDIRECT, Drupal\cas\Service\CasHelper::EVENT_PRE_VALIDATE_SERVER_CONFIG, Drupal\cas\Service\CasHelper::EVENT_PRE_VALIDATE, Drupal\cas\Service\CasHelper::EVENT_POST_VALIDATE, Drupal\cas\Service\CasHelper::EVENT_POST_LOGIN. Use the corresponding event class fully qualified names instead: Drupal\cas\Service\CasHelper::EVENT_PRE_USER_LOAD -> Drupal\cas\Event\CasPreUserLoadEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_USER_LOAD_REDIRECT -> Drupal\cas\Event\CasPreUserLoadRedirectEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_REGISTER -> Drupal\cas\Event\CasPreRegisterEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_LOGIN -> Drupal\cas\Event\CasPreLoginEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_REDIRECT -> Drupal\cas\Event\CasPreRedirectEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_VALIDATE_SERVER_CONFIG -> Drupal\cas\Event\CasPreValidateServerConfigEvent, Drupal\cas\Service\CasHelper::EVENT_PRE_VALIDATE -> Drupal\cas\Event\CasPreValidateEvent, Drupal\cas\Service\CasHelper::EVENT_POST_VALIDATE -> Drupal\cas\Event\CasPostValidateEvent, Drupal\cas\Service\CasHelper::EVENT_POST_LOGIN -> Drupal\cas\Event\CasPostLoginEvent. See https://www.drupal.org/node/3462792';
    $this->expectDeprecation($message);

    // Extract the CAS log entry from the stack.
    $log = array_reduce(
      $this->container->get('cas_test.logger')->cleanLogs(),
      function (?array $previousEntry, array $entry): ?array {
        return $entry[2]['channel'] === 'cas' ? $entry : $previousEntry;
      },
    );
    // Check that the warning has been logged.
    $this->assertSame(RfcLogLevel::WARNING, $log[0]);
    $this->assertSame($message, $log[1]);

    // Assert that subscriber defined with deprecated constants are working.
    $eventDispatcher = $this->container->get('event_dispatcher');
    $state = $this->container->get('state');
    foreach (DeprecatedEventConstants::DEPRECATE_EVENT_IDS as $constantName => $className) {
      $eventDispatcher->dispatch(new Event(), constant($constantName));
      $this->assertTrue($state->get('cas.test_event_constant.' . $className));
    }
  }

}
