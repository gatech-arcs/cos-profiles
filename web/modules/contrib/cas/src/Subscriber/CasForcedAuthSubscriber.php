<?php

declare(strict_types=1);

namespace Drupal\cas\Subscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cas\CasRedirectData;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasRedirector;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for implementing CAS forced authentication.
 */
class CasForcedAuthSubscriber extends HttpExceptionSubscriberBase {

  /**
   * Is forced login configuration setting enabled.
   */
  protected bool $forcedLoginEnabled = FALSE;

  /**
   * Paths to check for forced login.
   */
  protected array $forcedLoginPaths = [];

  public function __construct(
    protected readonly RouteMatchInterface $routeMatcher,
    protected readonly AccountInterface $currentUser,
    protected readonly ExecutableManagerInterface $conditionManager,
    protected readonly CasHelper $casHelper,
    protected readonly CasRedirector $casRedirector,
  ) {
    $this->forcedLoginPaths = $casHelper->getCasSetting('forced_login.paths');
    $this->forcedLoginEnabled = $casHelper->getCasSetting('forced_login.enabled');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before DynamicPageCacheSubscriber (27) and
    // CasGatewayAuthSubscriber (28)
    // but after important services like RouterListener (32) and
    // MaintenanceModeSubscriber (30)
    $events[KernelEvents::REQUEST][] = ['onRequest', 29];

    $events[KernelEvents::EXCEPTION][] = ['onException', 0];
    return $events;
  }

  /**
   * Respond to kernel request set forced auth redirect response.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function onRequest(RequestEvent $event): void {
    // Don't do anything if this is a sub request and not a master request.
    if ($event->getRequestType() != HttpKernelInterface::MAIN_REQUEST) {
      return;
    }

    // Some routes we don't want to run on.
    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, CasHelper::IGNORABLE_AUTO_LOGIN_ROUTES)) {
      return;
    }

    // Only care about anonymous users.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    if (!$this->forcedLoginEnabled) {
      return;
    }

    // Check if user provided specific paths to force/not force a login.
    $condition = $this->conditionManager->createInstance('request_path', $this->forcedLoginPaths);
    assert($condition instanceof ExecutableInterface);
    if (!$this->conditionManager->execute($condition)) {
      return;
    }

    $this->casHelper->log(LogLevel::DEBUG, 'Initializing forced login auth from CasSubscriber.');

    // Start constructing the URL redirect to CAS for forced auth.
    // Add the current path to the service URL as the 'destination' param,
    // so that when the ServiceController eventually processes the login,
    // it knows to return the user back here.
    $request = $event->getRequest();
    $currentPath = str_replace($request->getSchemeAndHttpHost(), '', $request->getUri());
    $redirectData = new CasRedirectData(['destination' => $currentPath]);

    $response = $this->casRedirector->buildRedirectResponse($redirectData);
    if ($response) {
      $event->setResponse($response);
      // If there's a 'destination' parameter set on the current request,
      // remove it, otherwise Drupal's RedirectResponseSubscriber will send
      // users to that location instead of to our CAS server.
      $request->query->remove('destination');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats(): array {
    return ['html'];
  }

  /**
   * Handle 403 errors.
   *
   * Other request subscribers with a higher priority may intercept the request
   * and return a 403 before our request subscriber can handle it. In those
   * instances we handle the forced login redirect if applicable here instead,
   * using an exception subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event): void {
    $this->onRequest($event);
  }

}
