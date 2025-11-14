<?php

declare(strict_types=1);

namespace Drupal\cas\Subscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cas\CasRedirectData;
use Drupal\cas\Model\GatewayMethod;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasRedirector;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for implementing CAS gateway authentication.
 */
class CasGatewayAuthSubscriber implements EventSubscriberInterface {

  /**
   * If gateway is enabled or not.
   */
  protected bool $gatewayEnabled;

  /**
   * Recheck time for gateway auth check.
   */
  protected int $gatewayRecheckTime;


  /**
   * Paths to check for gateway login.
   */
  protected array $gatewayPaths = [];

  /**
   * The redirect method for gateway.
   */
  protected GatewayMethod $gatewayMethod;

  public function __construct(
    protected readonly RouteMatchInterface $routeMatcher,
    protected readonly AccountInterface $currentUser,
    protected readonly ExecutableManagerInterface $conditionManager,
    protected readonly CasHelper $casHelper,
    protected readonly CasRedirector $casRedirector,
  ) {
    $this->gatewayEnabled = $casHelper->getCasSetting('gateway.enabled');
    $this->gatewayRecheckTime = $casHelper->getCasSetting('gateway.recheck_time');
    $this->gatewayPaths = $casHelper->getCasSetting('gateway.paths');
    $this->gatewayMethod = GatewayMethod::from($casHelper->getCasSetting('gateway.method'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before DynamicPageCacheSubscriber (27)
    // but after CasForcedAuthSubscriber (29),
    // MaintenanceModeSubscriber (30) and RouterListener (32)
    $events[KernelEvents::REQUEST][] = ['onRequest', 28];

    // Run before DynamicPageCacheSubscriber (100) so it can cache our
    // modification to responses.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 110];
    return $events;
  }

  /**
   * Respond to request events.
   *
   * This is the server-side implementation of CAS gateway authentication.
   *
   * This works by having Drupal return a redirect to the CAS server for the
   * gateway auth check. Caching is disabled on all paths this would be
   * active on. See the DenyCas response policy file.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    // Only implement gateway feature for GET requests, to prevent users from
    // being redirected to CAS server for things like form submissions.
    if (!$event->getRequest()->isMethod('GET')) {
      return;
    }

    if (!$this->gatewayEnabled || $this->gatewayMethod !== GatewayMethod::ServerSide) {
      return;
    }

    // Only care about anonymous users.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // Some routes we don't want to run on.
    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, CasHelper::IGNORABLE_AUTO_LOGIN_ROUTES)) {
      return;
    }

    // Don't do anything if this is a request from cron, drush, crawler, etc.
    if ($this->isCrawlerRequest($event->getRequest())) {
      return;
    }

    // User can indicate specific paths to enable (or disable) gateway mode.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($this->gatewayPaths);
    if (!$this->conditionManager->execute($condition)) {
      return;
    }

    // Don't perform the auth check if we just returned from one.
    // This prevents an infinite redirect loop.
    $session = $event->getRequest()->getSession();
    if ($session && $session->has('cas_temp_disable_gateway_auth')) {
      $session->remove('cas_temp_disable_gateway_auth');
      $this->casHelper->log(LogLevel::DEBUG, "CAS gateway auth temporary disable flag set, skipping.");
      return;
    }

    // Do nothing if we're configured to only check once per period of time
    // and that length of time hasn't passed yet. The cookie is time-based
    // and browsers will stop sending it after it has expired. That's when we
    // know it's OK to check again.
    if ($this->gatewayRecheckTime > 0 && $event->getRequest()->cookies->has('cas_gateway_checked_ss')) {
      $this->casHelper->log(LogLevel::DEBUG, 'CAS gateway auth has already been performed recently, skipping.');
      return;
    }

    // Start constructing the URL redirect to CAS for gateway auth.
    // Add the current path to the service URL as the 'destination' param,
    // so that when the ServiceController eventually processes the login,
    // it knows to return the user back here.
    $currentPath = str_replace($event->getRequest()->getSchemeAndHttpHost(), '', $event->getRequest()->getUri());
    $redirectData = new CasRedirectData([
      'destination' => $currentPath,
      'from_gateway' => TRUE,
    ], ['gateway' => 'true']);
    $this->casHelper->log(LogLevel::DEBUG, 'Initializing gateway auth from CasSubscriber.');

    $response = $this->casRedirector->buildRedirectResponse($redirectData);
    if ($response) {
      // If configured to only perform gateway auth check once per period of
      // time, then set a cookie for that period of time to prevent future
      // auth checks.
      if ($this->gatewayRecheckTime > 0) {
        $expireTime = time() + (60 * $this->gatewayRecheckTime);
        $cookie = Cookie::create('cas_gateway_checked_ss', '1', $expireTime);
        $response->headers->setCookie($cookie);
      }
      $event->setResponse($response);

      // If there's a 'destination' parameter set on the current request,
      // remove it, otherwise Drupal's RedirectResponseSubscriber will send
      // users to that location instead of the CAS server.
      $event->getRequest()->query->remove('destination');

      // Set a temp session var that will block our subscriber from doing
      // anything on the next request. Without this, we'll create a redirect
      // loop as the gateway subscriber will just immediately redirect the user
      // back to the CAS server again.
      if ($this->gatewayMethod === GatewayMethod::ServerSide) {
        $event->getRequest()->getSession()->set('cas_temp_disable_gateway_auth', TRUE);
      }
    }
  }

  /**
   * Handle response event.
   *
   * This is the client-side implementation (JS) of CAS gateway authentication.
   *
   * This works by attaching a JS library to the HTML response that will
   * redirect the user agent to the CAS server for the gateway check.
   *
   * Unlike the server-side implementation, this one works with page caching
   * so we need to set appropriate cache metadata.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    // Only implement gateway feature for GET requests, to prevent users from
    // being redirected to CAS server for things like form submissions.
    if (!$event->getRequest()->isMethod('GET')) {
      return;
    }

    // Build up some cache metadata we'll need to attach to the response.
    $cacheMetadata = new CacheableMetadata();
    $response = $event->getResponse();

    // Make sure we're configured for client-side gateway auth.
    $cacheMetadata->addCacheTags(['config:cas.settings']);
    if (!$this->gatewayEnabled || $this->gatewayMethod !== GatewayMethod::ClientSide) {
      $this->addCacheMetadataToResponse($response, $cacheMetadata);
      return;
    }

    // Only care about anonymous users.
    $cacheMetadata->addCacheContexts(['user.roles:authenticated']);
    if ($this->currentUser->isAuthenticated()) {
      $this->addCacheMetadataToResponse($response, $cacheMetadata);
      return;
    }

    $cacheMetadata->addCacheContexts(['request_format']);
    if (!$response instanceof HtmlResponse) {
      $this->addCacheMetadataToResponse($response, $cacheMetadata);
      return;
    }

    // Some routes we don't want to run on.
    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, CasHelper::IGNORABLE_AUTO_LOGIN_ROUTES)) {
      return;
    }

    // Check that the path matches what's been configured.
    // The cache context on the request path condition plugin is url.path
    // which is an "expensive" context, as it makes dynamic page cache
    // quite useless for serving 404 pages. Avoid it if we know the only
    // path we care about is the front page, which is a common config for
    // gateway.
    if (trim($this->gatewayPaths['pages']) === '<front>') {
      $cacheMetadata->addCacheContexts(['url.path.is_front']);
    }
    else {
      $cacheMetadata->addCacheContexts(['url.path']);
    }
    $condition = $this->conditionManager->createInstance('request_path');
    $gatewayPaths = $this->gatewayPaths;
    $condition->setConfiguration($gatewayPaths);
    if (!$this->conditionManager->execute($condition)) {
      $this->addCacheMetadataToResponse($response, $cacheMetadata);
      return;
    }

    // We're good to activate gateway redirect. Add all the cache metadata we've
    // built up to the existing page response and add our front-end library
    // that performs the redirect.
    $this->addCacheMetadataToResponse($response, $cacheMetadata);

    // Start constructing the URL redirect to CAS for gateway auth.
    // Add the current path to the service URL as the 'destination' param,
    // so that when the ServiceController eventually processes the login,
    // it knows to return the user back here.
    $request = $event->getRequest();
    $currentPath = str_replace($request->getSchemeAndHttpHost(), '', $request->getUri());
    $redirectData = new CasRedirectData([
      'destination' => $currentPath,
      'from_gateway' => TRUE,
    ], ['gateway' => 'true']);

    $redirectResponse = $this->casRedirector->buildRedirectResponse($redirectData);
    if ($redirectResponse) {
      // Add our JS library used for redirecting and provide the redirect URL
      // and check frequency.
      $attachments = [];
      $attachments['library'][] = 'cas/client_side_gateway_redirect';
      $attachments['drupalSettings']['cas'] = [
        'gatewayRedirectUrl' => $redirectResponse->getTargetUrl(),
        'recheckTime' => $this->gatewayRecheckTime,
        // We pass the list of known crawlers to the client so it knows not
        // to activate the redirect if the request comes from these. If we did
        // this check server-side, it wouldn't be compatible with caching
        // without varying every cache entry by user agent which is not
        // practical.
        'knownCrawlers' => implode('|', $this->getKnownCrawlersList()),
      ];

      $response->addAttachments($attachments);
    }
  }

  /**
   * Add cache metadata to a response if it supports it.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response object.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheableMetadata
   *   The cache metadata to add.
   */
  private function addCacheMetadataToResponse(Response $response, CacheableMetadata $cacheableMetadata): void {
    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($cacheableMetadata);
    }
  }

  /**
   * Check is the current request is from a known list of web crawlers.
   *
   * We don't want to perform any CAS redirects in this case, because crawlers
   * need to be able to index the pages.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return bool
   *   True if the request is coming from a crawler, false otherwise.
   */
  private function isCrawlerRequest(Request $request): bool {
    if ($request->server->get('HTTP_USER_AGENT')) {
      $crawlers = $this->getKnownCrawlersList();
      // Return on the first find.
      foreach ($crawlers as $c) {
        if (stripos($request->server->get('HTTP_USER_AGENT'), $c) !== FALSE) {
          $this->casHelper->log(LogLevel::DEBUG, 'CasSubscriber ignoring request from suspected crawler "%crawler"', ['%crawler' => $c]);
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Return a list of known web crawlers as they appear in user agent strings.
   *
   * @return string[]
   *   The list of crawlers snippets.
   */
  private function getKnownCrawlersList(): array {
    return [
      // cspell:disable
      'Google',
      'msnbot',
      'Rambler',
      'Yahoo',
      'AbachoBOT',
      'accoona',
      'AcoiRobot',
      'ASPSeek',
      'CrocCrawler',
      'Dumbot',
      'FAST-WebCrawler',
      'GeonaBot',
      'Gigabot',
      'Lycos',
      'MSRBOT',
      'Scooter',
      'AltaVista',
      'IDBot',
      'eStyle',
      'Scrubby',
      'gsa-crawler',
      // cspell:enable
    ];
  }

}
