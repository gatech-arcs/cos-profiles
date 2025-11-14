<?php

declare(strict_types=1);

namespace Drupal\cas\Exception;

use Drupal\Component\Render\MarkupInterface;
use Drupal\cas\Model\CasLoginExceptionType;

/**
 * Exception occurring on login failure.
 */
class CasLoginException extends \Exception {

  /**
   * Auto registration turned off, and local account does not exist.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::NoLocalAccount instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const NO_LOCAL_ACCOUNT = 1;

  /**
   * Auto reg turned on, but subscriber denied auto reg.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::SubscriberDeniedRegistration
   *   instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const SUBSCRIBER_DENIED_REG = 2;

  /**
   * Could not log user in, because Drupal account is blocked.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::AccountBlocked instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const ACCOUNT_BLOCKED = 3;

  /**
   * Event listener prevented login.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::SubscriberDeniedLogin instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const SUBSCRIBER_DENIED_LOGIN = 4;

  /**
   * Error parsing CAS attributes during login.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::AttributeParsingError instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const ATTRIBUTE_PARSING_ERROR = 5;

  /**
   * Auto registration attempted to register Drupal user that already exists.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::UsernameAlreadyExists instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const USERNAME_ALREADY_EXISTS = 6;

  /**
   * Cannot log in until admins are not unblocking the new account.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\CasLoginExceptionType::AdminApprovalRequired instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const ADMIN_APPROVAL_REQUIRED = 7;

  /**
   * A user message when login failed on a subscriber cancellation.
   */
  protected MarkupInterface|string|null $subscriberCancelReason = NULL;

  public function __construct(string $message = '', CasLoginExceptionType|int $code = CasLoginExceptionType::Unknown, ?\Exception $previous = NULL) {
    $cases = CasLoginExceptionType::cases();
    if (is_int($code)) {
      $codeAsInt = $code;
      $code = $cases[$code];
      @trigger_error('Passing the exception code as integer is deprecated in cas:3.0.0 and removed from cas:3.1.0. use one of the \Drupal\cas\Model\ExceptionType enum cases instead. See https://www.drupal.org/node/3462792', E_USER_DEPRECATED);
    }
    else {
      $codeAsInt = array_search($code, $cases, TRUE);
    }
    parent::__construct($message, $codeAsInt, $previous);
    $this->code = $code;
  }

  /**
   * Sets a user message when login failed on a subscriber cancellation.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $reason
   *   A user message to be set along with the exception.
   *
   * @return $this
   */
  public function setSubscriberCancelReason(MarkupInterface|string|null $reason): static {
    $code = $this->getCode();
    if ($code === CasLoginExceptionType::SubscriberDeniedLogin || $code === CasLoginExceptionType::SubscriberDeniedRegistration) {
      $this->subscriberCancelReason = $reason;
    }
    return $this;
  }

  /**
   * Returns the user message if login failed on a subscriber cancellation.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason why login failed, if any.
   */
  public function getSubscriberCancelReason(): MarkupInterface|string|null {
    return $this->subscriberCancelReason;
  }

}
