<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\cas\CasRedirectData;
use Drupal\cas\CasServerConfig;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreRedirectEvent.
 *
 * CAS dispatches this event just before a user is redirected to the CAS server
 * for authentication.
 *
 * Subscribers of this event can:
 *  - Add query string parameters to the CAS server URL. This is useful if your
 *    CAS server requires extra data to be sent during authentication.
 *  - Add query string parameters to the "service URL" (the URL users are
 *    returned to after authenticating with the CAS server). This is useful if
 *    you want to pass data back to your Drupal site after the authentication
 *    process is completed.
 *  - Prevent the authentication redirect entirely. This is only applicable if
 *    the user was being redirected for a Forced Login or Gateway Login.
 *    Users that visit /caslogin (or /cas) will always be redirected to the CAS
 *    server no matter what.
 *  - Indicate if the redirect to the CAS server is a cacheable redirect and if
 *    so, add cache tags and cache contexts to the redirect response.
 */
class CasPreRedirectEvent extends Event {

  public function __construct(
    protected readonly CasRedirectData $casRedirectData,
    protected readonly CasServerConfig $casServerConfig,
  ) {}

  /**
   * Getter for $casRedirectData.
   *
   * @return \Drupal\cas\CasRedirectData
   *   The redirect data object.
   */
  public function getCasRedirectData(): CasRedirectData {
    return $this->casRedirectData;
  }

  /**
   * Get the CAS server config for the server to redirect to.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The config, which can be modified.
   */
  public function getCasServerConfig(): CasServerConfig {
    return $this->casServerConfig;
  }

}
