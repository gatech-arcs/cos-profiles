<?php

declare(strict_types=1);

namespace Drupal\cas\Subscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * Redirects logged-in users when they try accessing the CAS login route.
 *
 * The CAS login route is denied for logged-in users. This intercepts the access
 * denied exception and changes it to a redirect.
 *
 * This is similar to what core does when an authenticated user tries to
 * access the login page, except core redirects to the user profile page.
 * \Drupal\user\EventSubscriber\AccessDeniedSubscriber.
 */
class CasRedirectLoggedInUsersSubscriber extends HttpExceptionSubscriberBase {

  public function __construct(
    protected AccountInterface $account,
    protected UrlGeneratorInterface $urlGenerator,
  ) {}

  /**
   * {@inheritDoc}
   */
  protected function getHandledFormats(): array {
    return ['html'];
  }

  /**
   * {@inheritDoc}
   */
  protected static function getPriority(): int {
    // Use a higher priority than ExceptionLoggingSubscriber, because there's
    // no need to log the exception if we can redirect.
    // @see Drupal\Core\EventSubscriber\ExceptionLoggingSubscriber
    return 75;
  }

  /**
   * Redirects users when access is denied for CAS login routes.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event): void {
    $routeName = RouteMatch::createFromRequest($event->getRequest())->getRouteName();
    $casLoginRoutes = ['cas.login', 'cas.legacy_login'];
    if ($this->account->isAuthenticated() && in_array($routeName, $casLoginRoutes)) {
      // Send to the homepage, which is the same behavior for when a user
      // accessing the login route and successfully logs in. Maintain the
      // redirect destination if it exists on the request.
      $redirectParams = [];
      if ($event->getRequest()->query->has('destination')) {
        $redirectParams['destination'] = $event->getRequest()->query->get('destination');
        // Add an attribute indicating the destination param should not be
        // stripped by CasLoginRedirectSubscriber. It only makes sense to strip
        // it if we're redirecting to the CAS server for login, but we're not.
        $event->getRequest()->attributes->set('cas_preserve_destination_param', TRUE);
      }
      $event->setResponse(new RedirectResponse($this->urlGenerator->generate('<front>', $redirectParams)));
    }
  }

}
