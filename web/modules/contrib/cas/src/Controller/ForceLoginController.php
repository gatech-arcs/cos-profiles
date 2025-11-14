<?php

declare(strict_types=1);

namespace Drupal\cas\Controller;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\cas\CasRedirectData;
use Drupal\cas\CasRedirectResponse;
use Drupal\cas\Service\CasRedirector;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ForceLoginController.
 *
 * Used to force CAS authentication for anonymous users.
 */
class ForceLoginController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected CasRedirector $casRedirector,
  ) {}

  /**
   * Handles a page request for our forced login route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   */
  public function forceLogin(Request $request): TrustedRedirectResponse|CasRedirectResponse|null {
    // @todo What if CAS is not configured? Need to handle that case.
    $service_url_query_params = $request->query->all();
    $cas_redirect_data = new CasRedirectData($service_url_query_params);
    $cas_redirect_data->setIsCacheable(TRUE);
    return $this->casRedirector->buildRedirectResponse($cas_redirect_data, TRUE);
  }

}
