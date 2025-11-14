<?php

namespace Drupal\csp;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * Replace placeholders with a nonce value.
 *
 * @code
 * $element['#attached'] = [
 *   'placeholders' => [
 *     \Drupal::service('csp.nonce_builder')->getPlaceholderKey() => [
 *       '#lazy_builder' => ['csp.nonce_builder:renderNonce', []],
 *     ],
 *   ],
 *   'csp_nonce' => [
 *     'script' => [Csp::POLICY_UNSAFE_INLINE],
 *   ],
 * ];
 * @endcode
 */
class NonceBuilder {

  /**
   * A random string to use in the placeholder key.
   *
   * @var string
   */
  private string $randomKey;

  /**
   * Nonce Builder constructor.
   *
   * @param \Drupal\csp\Nonce $nonce
   *   The Nonce service.
   */
  public function __construct(
    public Nonce $nonce,
  ) {
    $this->randomKey = Crypt::randomBytesBase64(8);
  }

  /**
   * Get the placeholder key for replacing with a nonce value.
   *
   * @return string
   *   The placeholder key.
   */
  public function getPlaceholderKey(): string {
    return 'drupal-filter-placeholder:csp_nonce:' . $this->randomKey;
  }

  /**
   * Lazy Builder callback for rendering a nonce value.
   *
   * @return array{'#plain_text': string}
   *   A renderable array as expected by the renderer service.
   */
  #[TrustedCallback]
  public function renderNonce(): array {
    return [
      '#plain_text' => $this->nonce->getValue(),
    ];
  }

}
