<?php

declare(strict_types=1);

namespace Drupal\cas\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreValidateEvent.
 *
 * CAS dispatches this event during the authentication process when the CAS
 * service ticket is being validated.
 *
 * Subscribers of this event can:
 *  - Set a non-standard validation path on the CAS server. Usually
 *    this value is determined automatically based on the CAS protocol version
 *    of the CAS server.
 *  - Add query string parameters to the CAS server validation URL. This is
 *    useful if your CAS server requires some custom data during the ticket
 *    validation process.
 */
class CasPreValidateEvent extends Event {

  public function __construct(
    protected string $validationPath,
    protected array $parameters,
  ) {}

  /**
   * Getter for $validationPath.
   *
   * @return string
   *   The validation path.
   */
  public function getValidationPath(): string {
    return $this->validationPath;
  }

  /**
   * Setter for $validationPath.
   *
   * @param string $validation_path
   *   The validation path to be used.
   */
  public function setValidationPath(string $validation_path): void {
    $this->validationPath = $validation_path;
  }

  /**
   * Getter for $parameters.
   *
   * @return array
   *   Query parameters to add to the validation URL.
   */
  public function getParameters(): array {
    return $this->parameters;
  }

  /**
   * Sets a single parameter.
   *
   * @param string $key
   *   The key of the parameter.
   * @param mixed $value
   *   The value of the parameter.
   */
  public function setParameter(string $key, mixed $value): void {
    $this->parameters[$key] = $value;
  }

  /**
   * Adds an array of parameters the existing set.
   *
   * @param array $parameters
   *   The parameters to be merged.
   */
  public function addParameters(array $parameters): void {
    $this->parameters = array_merge($this->parameters, $parameters);
  }

}
