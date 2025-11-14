<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Drupal\user\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPostLoginEvent.
 *
 * CAS dispatches this event during the authentication process after the user
 * has been logged into Drupal.
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPostLoginEvent extends Event {

  public function __construct(
    protected readonly UserInterface $account,
    protected readonly CasPropertyBag $casPropertyBag,
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

}
