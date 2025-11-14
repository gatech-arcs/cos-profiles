<?php

declare(strict_types=1);

namespace Drupal\cas\Service;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\cas\Model\CasLoginExceptionType;
use Drupal\cas\Model\EmailAssignment;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\user\UserInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the 'cas.user_manager' service default implementation.
 */
class CasUserManager {

  /**
   * Email address for new users is combo of username + custom hostname.
   *
   * @var int
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\EmailAssignment::Standard instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const EMAIL_ASSIGNMENT_STANDARD = 0;

  /**
   * Email address for new users is derived from a CAS attribute.
   *
   * @var int
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *    \Drupal\cas\Model\EmailAssignment::FromAttribute instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const EMAIL_ASSIGNMENT_ATTRIBUTE = 1;

  /**
   * The CAS provider ID.
   */
  protected const PROVIDER = 'cas';

  /**
   * Whether admin approval is required on new user accounts registration.
   */
  protected bool $adminApprovalNeeded;

  public function __construct(
    protected readonly ExternalAuthInterface $externalAuth,
    protected readonly AuthmapInterface $authmap,
    protected readonly ConfigFactoryInterface $configFactory,
    protected SessionInterface $session,
    protected Connection $connection,
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly CasHelper $casHelper,
    protected readonly CasProxyHelper $casProxyHelper,
    protected readonly PasswordGeneratorInterface $passwordGenerator,
  ) {}

  /**
   * Register a local Drupal user given a CAS username.
   *
   * @param string $authname
   *   The CAS username.
   * @param string $local_username
   *   The local Drupal username to be created.
   * @param array $property_values
   *   (optional) Property values to assign to the user on registration.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the newly registered user.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   When the user account could not be registered.
   */
  public function register(string $authname, string $local_username, array $property_values = []): UserInterface {
    $property_values['name'] = $local_username;
    $property_values['pass'] = $this->randomPassword();
    // Respect a previous status set by any of the upstream subscribers.
    $property_values['status'] ??= (int) !$this->isAdminApprovalNeeded();

    try {
      $user = $this->externalAuth->register($authname, static::PROVIDER, $property_values);
    }
    catch (ExternalAuthRegisterException $e) {
      throw new CasLoginException($e->getMessage(), CasLoginExceptionType::UsernameAlreadyExists);
    }
    return $user;
  }

  /**
   * Attempts to log the user in to the Drupal site.
   *
   * @param \Drupal\cas\CasPropertyBag $property_bag
   *   CasPropertyBag containing username and attributes from CAS.
   * @param string $ticket
   *   The service ticket.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown if there was a problem logging in the user.
   */
  public function login(CasPropertyBag $property_bag, string $ticket): void {
    $account = $this->externalAuth->load($property_bag->getUsername(), static::PROVIDER);
    if ($account === FALSE) {
      // Check if we should create the user or not.
      if ($this->casHelper->getCasSetting('user_accounts.auto_register') === TRUE) {
        $this->casHelper->log(
          LogLevel::DEBUG,
          'Existing account not found for user, attempting to auto-register.'
        );

        // Dispatch an event that allows modules to deny automatic registration
        // for this user account or to set properties for the user that will
        // be created.
        $cas_pre_register_event = new CasPreRegisterEvent($property_bag);
        $cas_pre_register_event->setPropertyValue('mail', $this->getEmailForNewAccount($property_bag));
        $this->casHelper->log(LogLevel::DEBUG, 'Dispatching \Drupal\cas\Event\CasPreRegisterEvent.');
        $this->eventDispatcher->dispatch($cas_pre_register_event);
        $this->eventDispatcher->dispatch($cas_pre_register_event, 'cas.pre_register');
        if ($cas_pre_register_event->getAllowAutomaticRegistration()) {
          $account = $this->register($property_bag->getUsername(), $cas_pre_register_event->getDrupalUsername(), $cas_pre_register_event->getPropertyValues());
          if ($this->isAdminApprovalNeeded() && $account->isBlocked()) {
            // Cannot log in until the admins are not approving the new account.
            // Note that CAS module provides, by default, the normal Drupal
            // behavior by showing a status message and, if configured, sending
            // email notifications to user and admins. This is achieved by
            // listening to ExternalAuthEvents::REGISTER event. Third-party may
            // override this behavior by providing a subscriber with a higher
            // priority, implementing their logic and stopping the event
            // propagation.
            // @see \Drupal\cas\Subscriber\CasAdminApprovalRegistrationSubscriber
            $this->casHelper->log(LogLevel::DEBUG, 'Login denied as new account needs admin approval.');
            throw new CasLoginException("Cannot login, admin approval is required for new accounts", CasLoginExceptionType::AdminApprovalRequired);
          }
        }
        else {
          $reason = $cas_pre_register_event->getCancelRegistrationReason();
          throw (new CasLoginException(
            sprintf("Registration of user '%s' denied by an event listener.", $property_bag->getUsername()),
            CasLoginExceptionType::SubscriberDeniedRegistration,
          ))->setSubscriberCancelReason($reason);
        }
      }
      else {
        throw new CasLoginException("Cannot login, local Drupal user account does not exist.", CasLoginExceptionType::NoLocalAccount);
      }
    }

    // Check if the retrieved user is blocked before moving forward.
    if ($account->isBlocked()) {
      throw new CasLoginException(sprintf('The username %s has not been activated or is blocked.', $account->getAccountName()), CasLoginExceptionType::AccountBlocked);
    }

    // Dispatch an event that allows modules to prevent this user from logging
    // in and/or alter the user entity before we save it.
    $pre_login_event = new CasPreLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching \Drupal\cas\Event\CasPreLoginEvent.');
    $this->eventDispatcher->dispatch($pre_login_event);
    $this->eventDispatcher->dispatch($pre_login_event, 'cas.pre_login');

    // Save user entity since event listeners may have altered it.
    // @todo Don't take it for granted. Find if the account was really altered.
    // @todo Should this be swapped with the following if(...) block? Why
    //   altering the account if the login has been denied?
    $account->save();

    if (!$pre_login_event->getAllowLogin()) {
      $reason = $pre_login_event->getCancelLoginReason();
      throw (new CasLoginException('Cannot login, an event listener denied access.', CasLoginExceptionType::SubscriberDeniedLogin))
        ->setSubscriberCancelReason($reason);
    }

    $this->externalAuth->userLoginFinalize($account, $property_bag->getUsername(), static::PROVIDER);
    $this->storeLoginSessionData($ticket);
    $this->session->set('is_cas_user', TRUE);
    $this->session->set('cas_username', $property_bag->getOriginalUsername());

    $postLoginEvent = new CasPostLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching \Drupal\cas\Event\CasPostLoginEvent.');
    $this->eventDispatcher->dispatch($postLoginEvent);
    $this->eventDispatcher->dispatch($postLoginEvent, 'cas.post_login');

    if ($this->casHelper->getCasSetting('proxy.initialize') && $property_bag->getPgt()) {
      $this->casHelper->log(LogLevel::DEBUG, "Storing PGT information for this session.");
      $this->casProxyHelper->storePgtSession($property_bag->getPgt());
    }
  }

  /**
   * Store the Session ID and ticket for single-log-out purposes.
   *
   * @param string $ticket
   *   The CAS service ticket to be used as the lookup key.
   */
  protected function storeLoginSessionData(string $ticket): void {
    if ($this->casHelper->getCasSetting('logout.enable_single_logout') === TRUE) {
      // @todo We should not access the session ID here. We at least need to
      // first persist the session so a proper ID is generated first.
      // See https://www.drupal.org/project/cas/issues/3190842.
      $session_id = $this->session->getId();
      $this->connection->upsert('cas_login_data')
        ->fields(
          ['sid', 'plainsid', 'ticket', 'created'],
          [Crypt::hashBase64($session_id), $session_id, $ticket, time()]
        )
        ->key('sid')
        ->execute();
    }
  }

  /**
   * Return CAS username for account, or FALSE if it doesn't have one.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool|string
   *   The CAS username if it exists, or FALSE otherwise.
   */
  public function getCasUsernameForAccount(int $uid): bool|string {
    return $this->authmap->get($uid, static::PROVIDER);
  }

  /**
   * Return uid of account associated with passed in CAS username.
   *
   * @param string $cas_username
   *   The CAS username to lookup.
   *
   * @return ?int
   *   The user ID of the user associated with the $cas_username or NULL.
   */
  public function getUidForCasUsername(string $cas_username): ?int {
    $uid = $this->authmap->getUid($cas_username, static::PROVIDER);
    return $uid ? (int) $uid : NULL;
  }

  /**
   * Save an association of the passed in Drupal user account and CAS username.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   * @param string $cas_username
   *   The CAS username.
   */
  public function setCasUsernameForAccount(UserInterface $account, string $cas_username): void {
    $this->authmap->save($account, static::PROVIDER, $cas_username);
  }

  /**
   * Remove the CAS username association with the provided user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   */
  public function removeCasUsernameForAccount(UserInterface $account): void {
    $this->authmap->delete((int) $account->id(), static::PROVIDER);
  }

  /**
   * Generate a random password for new user registrations.
   *
   * @return string
   *   A random password.
   */
  protected function randomPassword(): string {
    // Default length is 10, use a higher number that's harder to brute force.
    return $this->passwordGenerator->generate(30);
  }

  /**
   * Return the email address that should be assigned to an auto-register user.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag associated with the user's login attempt.
   *
   * @return string
   *   The email address.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown when the email address cannot be derived properly.
   */
  public function getEmailForNewAccount(CasPropertyBag $cas_property_bag): string {
    $email_assignment_strategy = EmailAssignment::from($this->casHelper->getCasSetting('user_accounts.email_assignment_strategy'));
    if ($email_assignment_strategy === EmailAssignment::Standard) {
      return $cas_property_bag->getUsername() . '@' . $this->casHelper->getCasSetting('user_accounts.email_hostname');
    }
    elseif ($email_assignment_strategy === EmailAssignment::FromAttribute) {
      $email_attribute = $this->casHelper->getCasSetting('user_accounts.email_attribute');
      if (empty($email_attribute) || !array_key_exists($email_attribute, $cas_property_bag->getAttributes())) {
        throw new CasLoginException('Specified CAS email attribute does not exist.', CasLoginExceptionType::AttributeParsingError);
      }

      $val = $cas_property_bag->getAttributes()[$email_attribute];
      if (empty($val)) {
        throw new CasLoginException('Empty data found for CAS email attribute.', CasLoginExceptionType::AttributeParsingError);
      }

      // The attribute value may actually be an array of values, but we need it
      // to only contain 1 value.
      if (is_array($val) && count($val) !== 1) {
        throw new CasLoginException('Specified CAS email attribute was formatted in an unexpected way.', CasLoginExceptionType::AttributeParsingError);
      }

      if (is_array($val)) {
        $val = $val[0];
      }

      return trim($val);
    }
    else {
      throw new CasLoginException('Invalid email address assignment type for auto user registration specified in settings.');
    }
  }

  /**
   * Checks whether Drupal requires admin approval when registering new users.
   *
   * @return bool
   *   Whether Drupal requires admin approval when registering new users.
   */
  protected function isAdminApprovalNeeded(): bool {
    if (!isset($this->adminApprovalNeeded)) {
      $auto_register_follow_registration_policy = $this->casHelper->getCasSetting('user_accounts.auto_register_follow_registration_policy');
      $user_settings = $this->configFactory->get('user.settings');
      $this->adminApprovalNeeded = $auto_register_follow_registration_policy && $user_settings->get('register') === UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL;
    }
    return $this->adminApprovalNeeded;
  }

}
