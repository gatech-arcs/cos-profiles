<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * Interface for enums with a Drupal form API options representation.
 */
interface OptionsInterface extends \BackedEnum {

  /**
   * Returns the enum cases as options suitable for Drupal form API.
   *
   * @return array
   *   List of options keyed by enum case value.
   */
  public static function getAsOptions(): array;

}
