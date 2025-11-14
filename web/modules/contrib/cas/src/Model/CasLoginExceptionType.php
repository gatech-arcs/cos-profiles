<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * Types of CasLoginExceptions.
 */
enum CasLoginExceptionType: string {

  // Unknown exception.
  case Unknown = '';

  // Auto-registration turned off, and local account does not exist.
  case NoLocalAccount = 'message_no_local_account';

  // Auto-registration turned on, but subscriber denied auto-registration.
  case SubscriberDeniedRegistration = 'message_subscriber_denied_reg';

  // Could not log user in, because Drupal account is blocked.
  case AccountBlocked = 'message_account_blocked';

  // An event listener prevented login.
  case SubscriberDeniedLogin = 'message_subscriber_denied_login';

  // Error parsing CAS attributes during login.
  case AttributeParsingError = 'message_validation_failure';

  // Auto-registration attempted to register Drupal user that already exists.
  case UsernameAlreadyExists = 'message_username_already_exists';

  // Cannot log in until admins are not unblocking the new account.
  case AdminApprovalRequired = '[no message]';

}
