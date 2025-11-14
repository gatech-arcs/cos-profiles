<?php

namespace Drupal\twig_casings\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters for various string casing conversions.
 */
class CasingExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('camel_case', fn($string) => $this->convertCase($string, 'camel')),
      new TwigFilter('kebab_case', fn($string) => $this->convertCase($string, 'kebab')),
      new TwigFilter('macro_case', fn($string) => $this->convertCase($string, 'macro')),
      new TwigFilter('pascal_case', fn($string) => $this->convertCase($string, 'pascal')),
      new TwigFilter('snake_case', fn($string) => $this->convertCase($string, 'snake')),
      new TwigFilter('train_case', fn($string) => $this->convertCase($string, 'train')),
    ];
  }

  /**
   * Converts a string to various casing formats.
   *
   * @param string $string
   *   The string to convert.
   * @param string $case
   *   The case style: camel, kebab, macro, pascal, snake, train.
   *
   * @return string
   *   The converted string.
   */
  private function convertCase(string $string, string $case): string {
    // Normalise the string to remove unwanted characters.
    $normalised = preg_replace('/[^a-zA-Z0-9]+/', ' ', $string ?? '');
    $normalised = trim($normalised);

    switch ($case) {
      case 'camel':
        $words = ucwords($normalised);
        return lcfirst(str_replace(' ', '', $words));

      case 'kebab':
        return strtolower(str_replace(' ', '-', $normalised));

      case 'macro':
        return strtoupper(str_replace(' ', '_', $normalised));

      case 'pascal':
        return str_replace(' ', '', ucwords($normalised));

      case 'snake':
        return strtolower(str_replace(' ', '_', $normalised));

      case 'train':
        return str_replace(' ', '-', ucwords($normalised));

      default:
        return $string;
    }
  }

}
