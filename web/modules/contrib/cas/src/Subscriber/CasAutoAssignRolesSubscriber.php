<?php

declare(strict_types=1);

namespace Drupal\cas\Subscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cas\Event\CasPreRegisterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a CasAutoAssignRoleSubscriber.
 */
class CasAutoAssignRolesSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CasPreRegisterEvent::class][] = ['assignRolesOnRegistration'];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Assign roles to a user that just registered via CAS.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The event object.
   */
  public function assignRolesOnRegistration(CasPreRegisterEvent $event): void {
    $auto_assigned_roles = $this->configFactory->get('cas.settings')->get('user_accounts.auto_assigned_roles');
    if (!empty($auto_assigned_roles)) {
      $event->setPropertyValue('roles', $auto_assigned_roles);
    }
  }

}
