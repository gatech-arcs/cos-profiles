<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasServerConfig;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreValidateServerConfigEvent.
 *
 * Subscribers of this event can modify the configuration of the CAS server
 * before it's used to make the validation request. A module could do this to
 * validate the ticket on some other server rather than the one defined in
 * the default configuration.
 */
class CasPreValidateServerConfigEvent extends Event {

  public function __construct(protected CasServerConfig $casServerConfig) {}

  /**
   * Returns the CAS server config.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The CAS server config.
   */
  public function getCasServerConfig(): CasServerConfig {
    return $this->casServerConfig;
  }

}
