<?php

declare(strict_types=1);

namespace Drupal\cas_attributes\Model;

use Drupal\Core\Url;

/**
 * Sync frequency options.
 */
enum SyncFrequency: int {

  case Never = 0;
  case InitialRegistration = 1;
  case EveryLogin = 2;

  /**
   * Returns the enum as form API options.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]
   *   Enum as form API options.
   */
  public static function asOptions(): array {
    return [
      SyncFrequency::Never->value => t('Never'),
      SyncFrequency::InitialRegistration->value => t('Initial registration only (requires "Auto register users" <a href="@link">CAS setting</a> be enabled).', ['@link' => Url::fromRoute('cas.settings')->toString()]),
      SyncFrequency::EveryLogin->value => t('Every login'),
    ];
  }

}
