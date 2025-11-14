<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreUserLoadEvent.
 *
 * The CAS module dispatches this event during the authentication process just
 * before an attempt is made to find a local Drupal user account that's
 * associated with the user attempting to login.
 *
 * Subscribers to this event can:
 *  - Alter the CAS username that is used when looking up an existing Drupal
 *    user account.
 */
class CasPreUserLoadEvent extends Event {

  public function __construct(protected CasPropertyBag $casPropertyBag) {}

  /**
   * CasPropertyBag getter.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The casPropertyBag property.
   */
  public function getCasPropertyBag(): CasPropertyBag {
    return $this->casPropertyBag;
  }

}
