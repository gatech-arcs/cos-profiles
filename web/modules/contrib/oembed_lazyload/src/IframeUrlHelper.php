<?php

namespace Drupal\oembed_lazyload;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;

/**
 * A helper class to generate a hash based on query parameters.
 */
class IframeUrlHelper {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * IFrameUrlHelper constructor.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(PrivateKey $private_key) {
    $this->privateKey = $private_key;
  }

  /**
   * Hashes oembed_lazyload query parameters.
   *
   * @param string $provider
   *   The oembed provider.
   * @param array $third_party_settings
   *   The third party settings.
   */
  public function getHash($provider, array $third_party_settings) {
    $data = $provider;
    foreach ($third_party_settings as $third_party_setting) {
      $data .= ":$third_party_setting";
    }
    return Crypt::hmacBase64($data, $this->privateKey->get() . Settings::getHashSalt());
  }

}
