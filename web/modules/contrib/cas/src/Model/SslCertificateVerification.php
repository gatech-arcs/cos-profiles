<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * Options for verifying the SSL/TLS certificate of CAS server.
 */
enum SslCertificateVerification: int implements OptionsInterface {

  // SSL configuration to use the system's CA bundle to verify CAS server.
  case Default = 0;

  // SSL configuration to use provided file to verify CAS server.
  case Custom = 1;

  // SSL Configuration to not verify CAS server.
  case None = 2;

  /**
   * {@inheritdoc}
   */
  public static function getAsOptions(): array {
    return [
      SslCertificateVerification::Default->value => t("Verify using your web server's default certificate authority (CA) chain."),
      SslCertificateVerification::None->value => t('Do not verify. (Note: this should NEVER be used in production.)'),
      SslCertificateVerification::Custom->value => t('Verify using a specific CA certificate. Use the field below to provide path (recommended).'),
    ];
  }

}
