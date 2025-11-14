<?php

namespace Drupal\typography_filter\Services;

/**
 * Provides an interface defining the typography filter service.
 */
interface TypographyFilterManagerInterface {

  /**
   * List of all available rules.
   */
  const DEFAULT_RULES = [
    'curlyquote' => 'CurlyQuote',
    'dash' => 'Dash',
    'dimension' => 'Dimension',
    'ellipsis' => 'Ellipsis',
    'frenchnobreakspace' => 'FrenchNoBreakSpace',
    'hyphen' => 'Hyphen',
    'nospacebeforecomma' => 'NoSpaceBeforeComma',
    'smartquotes' => 'SmartQuotes',
    'trademark' => 'Trademark',
    'unit' => 'Unit',
  ];

  /**
   * Process HTML content with fixer's rules.
   *
   * @param string $content
   *   HTML content needs to be processed.
   * @param array $rules
   *   Array of rules to apply. By default, all available rules apply.
   * @param string $locale
   *   Language code. By default, uses code of the current language.
   * @param array $protected_tags
   *   Array of HTML tag names that the DOM parser must avoid.
   *   Nothing in those tags will be fixed.
   *
   * @return string
   *   Processed HTML content.
   */
  public function fix(string $content, array $rules = [], string $locale = '', array $protected_tags = []): string;

  /**
   * Process non-HTML content with fixer's rules.
   *
   * @param string $content
   *   Content needs to be processed.
   * @param array $rules
   *   Array of rules to apply. By default, all available rules apply.
   * @param string $locale
   *   Language code. By default, uses code of the current language.
   *
   * @return string
   *   Processed content.
   */
  public function fixString(string $content, array $rules = [], string $locale = ''): string;

}
