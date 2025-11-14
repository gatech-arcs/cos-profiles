<?php

declare(strict_types=1);

namespace Drupal\cas_user_interaction_test;

use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\externalauth\ExternalAuthInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CasTestSubscriber.
 */
class CasUserInteractionTestSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly StateInterface $state,
    protected readonly ExternalAuthInterface $externalAuth,
    protected readonly PrivateTempStoreFactory $privateTempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasPreUserLoadRedirectEvent::class => 'onPreUserLoadRedirect',
    ];
  }

  /**
   * Redirects to a form that asks user to accept the site's 'Legal Notice'.
   *
   * @param \Drupal\cas\Event\CasPreUserLoadRedirectEvent $event
   *   The event.
   */
  public function onPreUserLoadRedirect(CasPreUserLoadRedirectEvent $event): void {
    $is_legal_notice_changed = $this->state->get('cas_user_interaction_test.changed', FALSE);
    $local_account = $this->externalAuth->load($event->getPropertyBag()->getUsername(), 'cas');
    // Add a redirect only if a local account exists (i.e. it's a login
    // operation) and the site's 'Legal Notice' has changed.
    if ($local_account && $is_legal_notice_changed) {
      $tempstore = $this->privateTempStoreFactory->get('cas_user_interaction_test');
      $tempstore->set('ticket', $event->getTicket());
      $tempstore->set('property_bag', $event->getPropertyBag());
      $event->setRedirectResponse(new RedirectResponse(Url::fromRoute('cas_user_interaction_test.form')->toString()));
    }
  }

}
