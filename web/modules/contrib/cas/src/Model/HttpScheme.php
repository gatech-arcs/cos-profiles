<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * HTTP protocols.
 */
enum HttpScheme: string implements OptionsInterface {

  case Http = 'http';

  case Https = 'https';

  /**
   * {@inheritdoc}
   */
  public static function getAsOptions(): array {
    return [
      self::Http->value => t('HTTP (non-secure)'),
      self::Https->value => t('HTTPS (secure)'),
    ];
  }

}
