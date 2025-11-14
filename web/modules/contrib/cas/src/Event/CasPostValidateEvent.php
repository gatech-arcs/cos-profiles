<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event fired after CAS validation happens.
 *
 * CAS dispatches this event just after the ticket is validated by the CAS
 * server and the CasPropertyBag object is created with relevant data from the
 * response.
 *
 * Subscribers of this event can parse the response from the CAS server and
 * modify the CasPropertyBag.
 */
class CasPostValidateEvent extends Event {

  public function __construct(
    protected readonly string $responseData,
    protected CasPropertyBag $casPropertyBag,
  ) {}

  /**
   * Returns the CasPropertyBag object.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The property bag
   */
  public function getCasPropertyBag(): CasPropertyBag {
    return $this->casPropertyBag;
  }

  /**
   * Returns the responseData string.
   *
   * @return string
   *   The raw validation response data from CAS server.
   */
  public function getResponseData(): string {
    return $this->responseData;
  }

}
