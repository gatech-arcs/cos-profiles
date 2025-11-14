<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows third-party code to inject user interaction into the flow.
 *
 * After a successful CAS login and validation, some third-party modules may
 * require to get some user input just before local logging in or before
 * registering a new local account.
 *
 * Potential use cases:
 * - There's a new version of site's 'Terms & Conditions' page. On the first
 *   login after the new version has been published, the user should accept the
 *   new terms or they cannot login.
 * - There's no local account and the site is configured with 'auto register'. A
 *   third-party module wants to allow the user to login with the local
 *   credentials so that the CAS account and the Drupal account get paired in
 *   the {authmap} table.
 *
 * Third-party modules that want to build this kind of interaction should listen
 * to \Drupal\cas\Event\CasPreUserLoadRedirectEvent::class event and set an HTTP
 * redirect response, using self::setRedirectResponse() provided by this class.
 * After fulfilling their logic, they're responsible to complete the process by
 * explicitly calling \Drupal\cas\Service\CasUserManager::login(), showing a
 * status message to user and executing the final redirect.
 */
class CasPreUserLoadRedirectEvent extends Event {

  /**
   * Subscribers may trigger an HTTP redirect.
   */
  protected ?RedirectResponse $redirectResponse = NULL;

  public function __construct(
    protected readonly string $ticket,
    protected CasPropertyBag $propertyBag,
    protected array $serviceParameters,
  ) {}

  /**
   * Returns the CAS property bag.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The CAS property bag.
   */
  public function getPropertyBag(): CasPropertyBag {
    return $this->propertyBag;
  }

  /**
   * Returns the CAS authentication ticket.
   *
   * @return string
   *   The CAS authentication ticket.
   */
  public function getTicket(): string {
    return $this->ticket;
  }

  /**
   * Returns the CAS service parameters.
   *
   * @return array
   *   The CAS service parameters.
   */
  public function getServiceParameters(): array {
    return $this->serviceParameters;
  }

  /**
   * Sets an HTTP redirect response.
   *
   * Subscribers may decide to trigger a redirect just after the attempt to
   * find a local Drupal user account.
   *
   * @param \Symfony\Component\HttpFoundation\RedirectResponse $redirect_response
   *   The HTTP redirect response to be set along with the event.
   *
   * @return $this
   */
  public function setRedirectResponse(RedirectResponse $redirect_response) {
    $this->redirectResponse = $redirect_response;
    return $this;
  }

  /**
   * Returns the HTTP redirect response.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   The HTTP redirect response or NULL, if none was set.
   */
  public function getRedirectResponse(): ?RedirectResponse {
    return $this->redirectResponse;
  }

}
