<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Drupal\Component\Render\MarkupInterface;
use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreRegisterEvent.
 *
 * The CAS module dispatches this event during the authentication process just
 * before a user is automatically registered to Drupal if:
 *  - Automatic user registration is enabled in the CAS module settings.
 *  - No existing Drupal account can be found that's associated with the
 *    CAS username of the user attempting authentication.
 *
 * Subscribers to this event can:
 *  - Prevent a Drupal account from being created for this user (thereby also
 *    preventing the user from logging in).
 *  - Change the username that will be assigned to the Drupal account. By
 *    default it is the same as the CAS username.
 *  - Set properties on the user account that will be created, like user roles
 *    or a custom first name field (for example by populating it with data from
 *    the CAS attributes available in $casPropertyBag).
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPreRegisterEvent extends Event {

  /**
   * Determines if this user will be allowed to auto-register or not.
   */
  protected bool $allowAutomaticRegistration = TRUE;

  /**
   * The username that will be assigned to the Drupal user account.
   *
   * By default this will be populated with the CAS username.
   */
  protected string $drupalUsername;

  /**
   * An array of property values to assign to the user account on registration.
   */
  protected array $propertyValues = [];

  /**
   * Contains the reason of registration cancellation.
   */
  protected MarkupInterface|string|null $cancelRegistrationReason = NULL;

  public function __construct(protected CasPropertyBag $casPropertyBag) {
    $this->drupalUsername = $casPropertyBag->getUsername();
  }

  /**
   * Return the CasPropertyBag of the event.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The $casPropertyBag property.
   */
  public function getCasPropertyBag(): CasPropertyBag {
    return $this->casPropertyBag;
  }

  /**
   * Retrieve the username that will be assigned to the Drupal account.
   *
   * @return string
   *   The username.
   */
  public function getDrupalUsername(): string {
    return $this->drupalUsername;
  }

  /**
   * Assign a different username to the Drupal account that is to be registered.
   *
   * @param string $username
   *   The username.
   */
  public function setDrupalUsername(string $username): void {
    $this->drupalUsername = $username;
  }

  /**
   * Return if this user is allowed to be auto-registered or not.
   *
   * @return bool
   *   TRUE if the user is allowed to be registered, FALSE otherwise.
   */
  public function getAllowAutomaticRegistration(): bool {
    return $this->allowAutomaticRegistration;
  }

  /**
   * Getter for propertyValues.
   *
   * @return array
   *   The user property values.
   */
  public function getPropertyValues(): array {
    return $this->propertyValues;
  }

  /**
   * Set a single property value for the user entity on registration.
   *
   * @param string $property
   *   The user entity property to set.
   * @param mixed $value
   *   The value of the property.
   */
  public function setPropertyValue(string $property, mixed $value): void {
    $this->propertyValues[$property] = $value;
  }

  /**
   * Set an array of property values for the user entity on registration.
   *
   * @param array $property_values
   *   The property values to set with each key corresponding to the property.
   */
  public function setPropertyValues(array $property_values): void {
    $this->propertyValues = array_merge($this->propertyValues, $property_values);
  }

  /**
   * Returns the reason of the registration cancellation.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason of registration cancellation.
   */
  public function getCancelRegistrationReason(): MarkupInterface|string|null {
    return $this->cancelRegistrationReason;
  }

  /**
   * Allows automatic registration.
   *
   * @return $this
   */
  public function allowAutomaticRegistration(): self {
    // Activate the allow automatic registration.
    $this->allowAutomaticRegistration = TRUE;
    $this->cancelRegistrationReason = NULL;
    return $this;
  }

  /**
   * Cancels automatic registration.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $reason
   *   The reason of automatic cancellation property to set.
   */
  public function cancelAutomaticRegistration(MarkupInterface|string|null $reason = NULL): self {
    // Set the reason code into the property.
    $this->cancelRegistrationReason = $reason;
    // Deactivate the allow automatic registration.
    $this->allowAutomaticRegistration = FALSE;
    return $this;
  }

}
