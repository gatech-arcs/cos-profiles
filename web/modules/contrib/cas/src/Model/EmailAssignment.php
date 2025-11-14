<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * Email assignment methods.
 */
enum EmailAssignment: int implements OptionsInterface {

  // Email address for new users is combination of username and custom hostname.
  case Standard = 0;

  // Email address for new users is derived from a CAS attribute.
  case FromAttribute = 1;

  /**
   * {@inheritdoc}
   */
  public static function getAsOptions(): array {
    return [
      EmailAssignment::Standard->value => t('Use the CAS username combined with a custom domain name you specify.'),
      EmailAssignment::FromAttribute->value => t("Use a CAS attribute that contains the user's complete email address."),
    ];
  }

}
