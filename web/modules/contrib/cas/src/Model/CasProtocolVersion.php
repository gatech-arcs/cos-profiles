<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * CAS protocol versions.
 */
enum CasProtocolVersion: string implements OptionsInterface {

  case Version1 = '1.0';

  case Version2 = '2.0';

  case Version3 = '3.0';

  /**
   * {@inheritdoc}
   */
  public static function getAsOptions(): array {
    return [
      self::Version1->value => t('1.0'),
      self::Version2->value => t('2.0'),
      self::Version3->value => t('3.0 or higher'),
    ];
  }

}
