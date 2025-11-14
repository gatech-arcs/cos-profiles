<?php

declare(strict_types=1);

namespace Drupal\cas\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\cas\Event\CasPreUserLoadEvent;
use Drupal\cas\Event\CasPreUserLoadRedirectEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Exception\CasSloException;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Model\CasLoginExceptionType;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasLogout;
use Drupal\cas\Service\CasUserManager;
use Drupal\cas\Service\CasValidator;
use Drupal\externalauth\ExternalAuthInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Controller used when redirect back from CAS authentication.
 */
class ServiceController implements ContainerInjectionInterface {

  use AutowireTrait;
  use StringTranslationTrait;

  public function __construct(
    protected readonly CasHelper $casHelper,
    protected readonly CasValidator $casValidator,
    protected readonly CasUserManager $casUserManager,
    protected readonly CasLogout $casLogout,
    protected readonly UrlGeneratorInterface $urlGenerator,
    protected readonly MessengerInterface $messenger,
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly ExternalAuthInterface $externalAuth,
  ) {}

  /**
   * Main point of communication between CAS server and the Drupal site.
   *
   * The path that this controller/action handle are always set to the "service"
   * url when authenticating with the CAS server, so CAS server communicates
   * back to the Drupal site using this controller action. That's why there's
   * so much going on in here - it needs to process a few different types of
   * requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   */
  public function handle(Request $request): Response {
    // First, check if this is a single-log-out (SLO) request from the server.
    if ($request->request->has('logoutRequest')) {
      try {
        $this->casLogout->handleSlo($request->request->get('logoutRequest'));
      }
      catch (CasSloException $e) {
        $this->casHelper->log(
          LogLevel::ERROR,
          'Error when handling single-log-out request: %error',
          ['%error' => $e->getMessage()]
        );
      }
      // Always return a 200 response. CAS Server doesn’t care either way what
      // happens here, since it is a fire-and-forget approach taken.
      return new Response('', 200);
    }

    /* If there is no ticket parameter on the request, the browser either:
     * (a) is returning from a gateway request to the CAS server in which
     *     the user was not already authenticated to CAS, so there is no
     *     service ticket to validate and nothing to do.
     * (b) has hit this URL for some other reason (crawler, curiosity, etc)
     *     and there is nothing to do.
     * In either case, we just want to redirect them away from this controller.
     */
    if (!$request->query->has('ticket')) {
      $this->casHelper->log(LogLevel::DEBUG, "No CAS ticket found in request to service controller; backing out.");
      return new RedirectResponse($this->urlGenerator->generate('<front>'));
    }

    // There is a ticket present, meaning CAS server has returned the browser
    // to the Drupal site so we can authenticate the user locally using the
    // ticket.
    $ticket = $request->query->get('ticket');

    // Our CAS service will need to reconstruct the original service URL
    // when validating the ticket. We always know what the base URL for
    // the service URL is (it's this page), but there may be some query params
    // attached as well (like a destination param) that we need to pass in
    // as well. So, detach the ticket param, and pass the rest off.
    $service_params = $request->query->all();
    unset($service_params['ticket']);
    try {
      $cas_validation_info = $this->casValidator->validateTicket($ticket, $service_params);
    }
    catch (CasValidateException $e) {
      // Validation failed, redirect to homepage and set message.
      $this->casHelper->log(
        LogLevel::ERROR,
        'Error when validating ticket: %error',
        ['%error' => $e->getMessage()]
      );
      $message_validation_failure = $this->casHelper->getMessage('error_handling.message_validation_failure');
      if (!empty($message_validation_failure)) {
        $this->messenger->addError($message_validation_failure);
      }

      return $this->createRedirectResponse($request, TRUE);
    }

    $this->casHelper->log(LogLevel::DEBUG, 'Starting login process for CAS user %username', ['%username' => $cas_validation_info->getUsername()]);

    // Dispatch an event that allows modules to alter any of the CAS data before
    // it's used to lookup a Drupal user account via the authmap table.
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching \Drupal\cas\Event\CasPreUserLoadEvent.');
    $event = new CasPreUserLoadEvent($cas_validation_info);
    $this->eventDispatcher->dispatch($event);
    $this->eventDispatcher->dispatch($event, 'cas.pre_user_load');

    if ($cas_validation_info->getUsername() !== $cas_validation_info->getOriginalUsername()) {
      $this->casHelper->log(
        LogLevel::DEBUG,
        'Username was changed from %original to %new from a subscriber.',
        [
          '%original' => $cas_validation_info->getOriginalUsername(),
          '%new' => $cas_validation_info->getUsername(),
        ]
      );
    }

    // At this point, the ticket is validated and third-party modules got the
    // chance to alter the username and also perform other 'pre user load'
    // tasks. Before authenticating the user locally, let's allow third-party
    // code to inject user interaction into the flow.
    // @see \Drupal\cas\Event\CasPreUserLoadRedirectEvent
    $cas_pre_user_load_redirect_event = new CasPreUserLoadRedirectEvent($ticket, $cas_validation_info, $service_params);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching \Drupal\cas\Event\CasPreUserLoadRedirectEvent.');
    $this->eventDispatcher->dispatch($cas_pre_user_load_redirect_event);
    $this->eventDispatcher->dispatch($cas_pre_user_load_redirect_event, 'cas.pre_user_load.redirect');

    // A subscriber might have set an HTTP redirect response allowing potential
    // user interaction to be injected into the flow.
    $redirect_response = $cas_pre_user_load_redirect_event->getRedirectResponse();
    if ($redirect_response) {
      $this->casHelper->log(LogLevel::DEBUG, 'Redirecting to @url as requested by one of \Drupal\cas\Event\CasPreUserLoadRedirectEvent event subscribers.', ['@url' => $redirect_response->getTargetUrl()]);
      return $redirect_response;
    }

    // Now that the ticket has been validated, we can use the information from
    // validation request to authenticate the user locally on the Drupal site.
    try {
      $this->casUserManager->login($cas_validation_info, $ticket);

      $login_success_message = $this->casHelper->getMessage('login_success_message');
      if (!empty($login_success_message)) {
        $this->messenger->addStatus($login_success_message);
      }
    }
    catch (CasLoginException $e) {
      // Use an appropriate log level depending on exception type.
      $error_level = match($e->getCode()) {
        CasLoginExceptionType::Unknown, CasLoginExceptionType::AttributeParsingError => LogLevel::ERROR,
        default => LogLevel::INFO,
      };

      $message = $e->getMessage();
      $reason = $e->getSubscriberCancelReason();
      if ($reason) {
        $message .= " (Reason: $reason)";
      }
      $this->casHelper->log($error_level, $message);

      // Display error message to the user, unless this login failure originated
      // from a gateway login. No sense in showing them an error when the login
      // is optional.
      $login_error_message = $this->getLoginErrorMessage($e);
      if ($login_error_message && !$request->query->has('from_gateway')) {
        $this->messenger->addError($login_error_message, 'error');
      }

      return $this->createRedirectResponse($request, TRUE);
    }

    return $this->createRedirectResponse($request);
  }

  /**
   * Create a redirect response that sends users somewhere after login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param bool $login_failed
   *   Indicates if the login failed or not.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  private function createRedirectResponse(Request $request, bool $login_failed = FALSE): RedirectResponse {
    // If login failed, we may have a failure page to send them to. Don't do it
    // if the request was from a gateway auth attempt though, as the login was
    // optional.
    if ($login_failed && $this->casHelper->getCasSetting('error_handling.login_failure_page') && !$request->query->has('from_gateway')) {
      // Remove 'destination' parameter, otherwise Drupal's
      // RedirectResponseSubscriber will send users to that location instead of
      // the failure page.
      $request->query->remove('destination');

      return new RedirectResponse(Url::fromUserInput($this->casHelper->getCasSetting('error_handling.login_failure_page'))->toString());
    }
    // Otherwise, send them to the homepage, or to the previous page they were
    // on when login was initiated (which is handled automatically via the
    // "destination" parameter).
    else {
      return new RedirectResponse($this->urlGenerator->generate('<front>'));
    }
  }

  /**
   * Get the error message to display when there is a login exception.
   *
   * @param \Drupal\cas\Exception\CasLoginException $e
   *   The login exception.
   *
   * @return \Drupal\Component\Render\MarkupInterface|array|string
   *   The error message.
   */
  private function getLoginErrorMessage(CasLoginException $e): MarkupInterface|array|string {
    $exceptionType = $e->getCode();
    assert($exceptionType instanceof CasLoginExceptionType);
    $msgKey = $exceptionType->value;

    if (in_array($exceptionType, [
      CasLoginExceptionType::SubscriberDeniedRegistration,
      CasLoginExceptionType::SubscriberDeniedLogin,
    ], TRUE)) {
      // If a subscriber has denied the registration or the login by setting a
      // custom message, use that message and exit here.
      if ($message = $e->getSubscriberCancelReason()) {
        return $message;
      }
    }
    elseif ($exceptionType === CasLoginExceptionType::AdminApprovalRequired) {
      // Don't show any message. User feedback is deferred to the CAS event
      // subscriber.
      // @see \Drupal\cas\Subscriber\CasAdminApprovalRegistrationSubscriber
      return '';
    }

    if (!empty($msgKey)) {
      if ($message = $this->casHelper->getMessage('error_handling.' . $msgKey)) {
        return $message;
      }
    }

    return $this->t('There was a problem logging in. Please contact a site administrator.');
  }

}
