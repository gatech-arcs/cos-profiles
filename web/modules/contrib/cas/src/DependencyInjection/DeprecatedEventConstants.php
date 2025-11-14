<?php

declare(strict_types=1);

namespace Drupal\cas\DependencyInjection;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRedirectEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Event\CasPreUserLoadEvent;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Event\CasPreValidateEvent;
use Drupal\cas\Event\CasPreValidateServerConfigEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

@trigger_error('The ' . __NAMESPACE__ . '\DeprecatedEventConstants is deprecated in cas:3.0.0 and is removed from cas:3.1.0. No replacement is provided. See https://www.drupal.org/node/3462792', E_USER_DEPRECATED);

/**
 * Compiler pass to detect deprecated event constants.
 *
 * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. No replacement is
 *   provided.
 *
 * @see https://www.drupal.org/node/3462792
 */
class DeprecatedEventConstants implements CompilerPassInterface {

  /**
   * List of deprecated constant names and their replacement.
   *
   * @var string[]
   */
  public const DEPRECATE_EVENT_IDS = [
    CasHelper::class . '::EVENT_PRE_USER_LOAD' => CasPreUserLoadEvent::class,
    CasHelper::class . '::EVENT_PRE_USER_LOAD_REDIRECT' => CasPreUserLoadRedirectEvent::class,
    CasHelper::class . '::EVENT_PRE_REGISTER' => CasPreRegisterEvent::class,
    CasHelper::class . '::EVENT_PRE_LOGIN' => CasPreLoginEvent::class,
    CasHelper::class . '::EVENT_PRE_REDIRECT' => CasPreRedirectEvent::class,
    CasHelper::class . '::EVENT_PRE_VALIDATE_SERVER_CONFIG' => CasPreValidateServerConfigEvent::class,
    CasHelper::class . '::EVENT_PRE_VALIDATE' => CasPreValidateEvent::class,
    CasHelper::class . '::EVENT_POST_VALIDATE' => CasPostValidateEvent::class,
    CasHelper::class . '::EVENT_POST_LOGIN' => CasPostLoginEvent::class,
  ];

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $definition = $container->getDefinition('event_dispatcher');
    $eventNames = array_map(fn(array $call): string => (string) $call[1][0], $definition->getMethodCalls());

    $deprecatedEventNames = [];
    foreach (self::DEPRECATE_EVENT_IDS as $constantName => $eventClass) {
      if (in_array(constant($constantName), $eventNames, TRUE)) {
        $deprecatedEventNames[] = $constantName;
      }
    }

    if ($deprecatedEventNames) {
      $message = sprintf(
        'The following event constants are deprecated: %s. Use the corresponding event class fully qualified names instead: %s. See https://www.drupal.org/node/3462792',
        implode(', ', $deprecatedEventNames),
        implode(', ', array_map(
          fn(string $constantName): string => $constantName . ' -> ' . self::DEPRECATE_EVENT_IDS[$constantName],
          $deprecatedEventNames,
        )),
      );
      if ($container->has('logger.factory')) {
        $container->get('logger.factory')->get('cas')->warning($message);
      }
      @trigger_error($message, E_USER_DEPRECATED);
    }
  }

}
