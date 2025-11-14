<?php

declare(strict_types=1);

namespace Drupal\cas;

/**
 * Data model for CAS property bag.
 */
class CasPropertyBag {

  /**
   * The original username as it has been received from the CAS server.
   */
  protected string $originalUsername;

  /**
   * The proxy granting ticket, if supplied.
   */
  protected ?string $pgt = NULL;

  /**
   * An array containing attributes returned from the server.
   */
  protected array $attributes = [];

  /**
   * Constructs a new value object instance.
   *
   * @param string $username
   *   The name of the CAS user.
   */
  public function __construct(protected string $username) {
    $this->originalUsername = $username;
  }

  /**
   * Username property setter.
   *
   * @param string $user
   *   The new username.
   */
  public function setUsername(string $user): void {
    $this->username = $user;
  }

  /**
   * Proxy granting ticket property setter.
   *
   * @param string $ticket
   *   The ticket to set as pgt.
   */
  public function setPgt(string $ticket): void {
    $this->pgt = $ticket;
  }

  /**
   * Username property getter.
   *
   * @return string
   *   The username property.
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Returns the original username property.
   *
   * @return string
   *   The original username.
   */
  public function getOriginalUsername(): string {
    return $this->originalUsername;
  }

  /**
   * Proxy granting ticket getter.
   *
   * @return string
   *   The pgt property.
   */
  public function getPgt(): string {
    return $this->pgt;
  }

  /**
   * Attributes property setter.
   *
   * @param array $cas_attributes
   *   An associative array containing attribute names as keys.
   */
  public function setAttributes(array $cas_attributes): void {
    $this->attributes = $cas_attributes;
  }

  /**
   * Cas attributes getter.
   *
   * @return array
   *   The attributes property.
   */
  public function getAttributes(): array {
    return $this->attributes;
  }

  /**
   * Adds a single attribute.
   *
   * @param string $name
   *   The attribute name.
   * @param mixed $value
   *   The attribute value.
   */
  public function setAttribute(string $name, mixed $value): void {
    $this->attributes[$name] = $value;
  }

  /**
   * Returns a single attribute if exists.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return mixed|null
   *   The attribute value, or NULL if it does not exist.
   */
  public function getAttribute(string $name): mixed {
    return $this->hasAttribute($name) ? $this->attributes[$name] : NULL;
  }

  /**
   * Checks whether an attribute exists.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return bool
   *   TRUE if the attribute exists, FALSE otherwise.
   */
  public function hasAttribute(string $name): bool {
    return isset($this->attributes[$name]);
  }

}
