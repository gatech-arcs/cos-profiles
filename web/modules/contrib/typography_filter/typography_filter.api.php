<?php

/**
 * @file
 * Hooks specific to the Typography Filter module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter fixer settings before being injected into the Fixer object.
 *
 * @param array &$settings
 *   The current fixer settings. An associative array with the following keys:
 *    - 'fixers': the list of the active fixer plugins
 *    - 'locale': the full locale identifier (i.e. fr_FR)
 * @param string $langcode
 *   The Drupal langcode of the content to be fixed.
 *
 * @see \Drupal\typography_filter\Plugin\Filter\FilterTypography::initFixer()
 * @note This hook is called on active modules and themes.
 */
function hook_typography_filter_settings_alter(array &$settings, string $langcode): void {
  if ($langcode === 'fr') {
    $settings['locale'] = 'fr_CA';
    $settings['fixers'][] = '\MyProject\Custom\Filter';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
