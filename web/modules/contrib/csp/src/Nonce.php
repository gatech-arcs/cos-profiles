<?php

namespace Drupal\csp;

use Drupal\Component\Utility\Crypt;

/**
 * Service for retrieving a per-request nonce value.
 */
class Nonce {

  /**
   * The request nonce.
   *
   * @var string
   */
  private readonly string $value;

  /**
   * Initialize the nonce service.
   */
  public function __construct() {
    // Nonce should be at least 128 bits.
    // @see https://www.w3.org/TR/CSP/#security-nonces
    $this->value = Crypt::randomBytesBase64(16);
  }

  /**
   * Return if a nonce value has been generated.
   *
   * @deprecated in csp:2.2.0 and is removed from csp:3.0.0. This method always returns TRUE.
   * @see https://www.drupal.org/node/3472461
   *
   * @return true
   *   A nonce value is always available after service is initialized.
   */
  public function hasValue(): bool {
    return TRUE;
  }

  /**
   * Get the nonce value.
   *
   * @return string
   *   A base64-encoded string.
   */
  public function getValue(): string {
    return $this->value;
  }

  /**
   * Get the nonce value formatted for inclusion in a directive.
   *
   * @return string
   *   The nonce in the format "'nonce-{value}'"
   */
  public function getSource(): string {
    return "'nonce-{$this->value}'";
  }

}
