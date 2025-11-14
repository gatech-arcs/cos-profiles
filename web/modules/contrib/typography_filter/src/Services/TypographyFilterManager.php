<?php

namespace Drupal\typography_filter\Services;

use Drupal\Core\Language\LanguageManagerInterface;
use JoliTypo\Fixer;

/**
 * The typography filter service.
 */
class TypographyFilterManager implements TypographyFilterManagerInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a new TypographyFilterManager instance.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function fix(string $content, array $rules = [], string $locale = '', array $protected_tags = []): string {
    $fixer = $this->initFixer($rules, $locale);

    if (!empty($protected_tags)) {
      $fixer->setProtectedTags($protected_tags);
    }

    return $fixer->fix($content);
  }

  /**
   * {@inheritdoc}
   */
  public function fixString(string $content, array $rules = [], string $locale = ''): string {
    $fixer = $this->initFixer($rules, $locale);

    return $fixer->fixString($content);
  }

  /**
   * Initializes a Fixer object.
   *
   * @param array $rules
   *   Array of rules to apply. By default, all available rules apply.
   * @param string $locale
   *   Language code. By default, uses code of the current language.
   *
   * @return \JoliTypo\Fixer
   *   Fixer.
   */
  protected function initFixer(array $rules = [], string $locale = ''): Fixer {
    $rules = empty($rules) ? self::DEFAULT_RULES : $rules;
    $locale = empty($locale) ? $this->languageManager->getCurrentLanguage()->getId() : $locale;
    $fixer = new Fixer($rules);
    $fixer->setLocale($locale);

    return $fixer;
  }

}
