<?php

declare(strict_types=1);

namespace Drupal\cas\Controller;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\cas\CasRedirectResponse;
use Drupal\cas\CasServerConfig;
use Drupal\cas\Model\CasProtocolVersion;
use Drupal\cas\Service\CasHelper;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class LogoutController.
 *
 * Logs a user out of Drupal then logs them out of the CAS server.
 */
class LogoutController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected readonly CasHelper $casHelper,
    protected readonly RequestStack $requestStack,
    protected readonly UrlGeneratorInterface $urlGenerator,
  ) {}

  /**
   * Logs a user out of Drupal, then redirects them to the CAS server logout.
   */
  public function logout(): SecuredRedirectResponse {
    // First log the user out of Drupal.
    user_logout();

    // Redirect the user to the CAS server for logging out there as well.
    $cas_server_logout_url = $this->getServerLogoutUrl($this->requestStack->getCurrentRequest());
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Drupal session terminated; redirecting to CAS server logout at %url",
      ['%url' => $cas_server_logout_url]
    );
    return new CasRedirectResponse($cas_server_logout_url, 302);
  }

  /**
   * Return the logout URL for the CAS server.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request, to provide base url context.
   *
   * @return string
   *   The fully constructed server logout URL.
   */
  public function getServerLogoutUrl(Request $request): string {
    // @todo Allow cas server config to be altered.
    $casServerConfig = CasServerConfig::createFromModuleConfig($this->casHelper->getCasSettings());
    $base_url = $casServerConfig->getServerBaseUrl() . 'logout';

    // CAS servers can redirect a user to some other URL after they end
    // the user session. Check if we're configured to send along this
    // destination parameter and pass it along if so.
    if (!empty($destination = $this->casHelper->getCasSetting('logout.logout_destination'))) {
      if ($destination === '<front>') {
        // If we have '<front>', resolve the path.
        $return_url = $this->urlGenerator->generate($destination, [], UrlGeneratorInterface::ABSOLUTE_URL);
      }
      elseif (UrlHelper::isExternal($destination)) {
        // If we have an absolute URL, use that.
        $return_url = $destination;
      }
      else {
        // This is a regular Drupal path.
        $return_url = $request->getSchemeAndHttpHost() . '/' . ltrim($destination, '/');
      }

      // CAS 2.0 uses 'url' param, while newer versions use 'service'.
      if ($casServerConfig->getProtocolVersion() === CasProtocolVersion::Version2) {
        $params['url'] = $return_url;
      }
      else {
        $params['service'] = $return_url;
      }

      return $base_url . '?' . UrlHelper::buildQuery($params);
    }
    else {
      return $base_url;
    }
  }

}
