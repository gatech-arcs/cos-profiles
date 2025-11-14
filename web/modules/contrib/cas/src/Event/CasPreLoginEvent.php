<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\Component\Render\MarkupInterface;
use Drupal\cas\CasPropertyBag;
use Drupal\user\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreLoginEvent.
 *
 * CAS dispatches this event during the authentication process after a local
 * Drupal user account has been loaded for the user attempting login, but
 * before the user is actually authenticated to Drupal.
 *
 * Subscribe to this event to:
 *  - Prevent the user from logging in by calling ::cancelLogin(). Additionally,
 *    a reason could be set when calling this method. If the reason was set,
 *    this message will be shown as error message to the user.
 *  - Change properties on the Drupal user account (like adding or removing
 *    roles). The CAS module saves the user entity after dispatching the
 *    event, so subscribers do not need to save it themselves.
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPreLoginEvent extends Event {

  /**
   * Controls whether or not the user will be allowed to login.
   */
  protected bool $allowLogin = TRUE;

  /**
   * The user message why logging-in has been canceled.
   */
  protected MarkupInterface|string|null $cancelLoginReason = NULL;

  public function __construct(
    protected UserInterface $account,
    protected CasPropertyBag $casPropertyBag,
  ) {}

  /**
   * CasPropertyBag getter.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The casPropertyBag property.
   */
  public function getCasPropertyBag(): CasPropertyBag {
    return $this->casPropertyBag;
  }

  /**
   * Return the user account entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user account entity.
   */
  public function getAccount(): UserInterface {
    return $this->account;
  }

  /**
   * Allows the login operation.
   *
   * @return $this
   */
  public function allowLogin() {
    $this->allowLogin = TRUE;
    $this->cancelLoginReason = NULL;
    return $this;
  }

  /**
   * Cancels the login operation.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $reason
   *   (optional) A user message explaining why the login has been canceled. If
   *   passed, this value will be used to show a message to the user that tries
   *   to login. If omitted, a standard message will be displayed.
   *
   * @return $this
   */
  public function cancelLogin(MarkupInterface|string|null $reason = NULL) {
    $this->allowLogin = FALSE;
    $this->cancelLoginReason = $reason;
    return $this;
  }

  /**
   * Return if this user is allowed to login.
   *
   * @return bool
   *   TRUE if the user is allowed to login, FALSE otherwise.
   */
  public function getAllowLogin(): bool {
    return $this->allowLogin;
  }

  /**
   * Returns a user message explaining why the login process is asked to cancel.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason why the login process is asked to cancel, if any has been set.
   */
  public function getCancelLoginReason(): MarkupInterface|string|null {
    return $this->cancelLoginReason;
  }

}
