<?php

/**
 * @file
 * Contains example implementations of the Fences hooks.
 */

/**
 * This hook is used to modify the fences field formatter settings form.
 *
 * NOTE, that if you add any form elements to the settings form, which you
 * would like to save, as a third party setting, you need to also implement
 * hook_config_schema_info_alter() to define the schema for the new settings.
 *
 * If you want to see a concrete example of a hook implementation with only
 * visual changes (no schema definition), see the fences_presets module.
 *
 * @param array $settingsForm
 *   The form array for the field formatter settings form.
 */
function hook_fences_field_formatter_third_party_settings_form_alter(array &$settingsForm) {
  // Add a custom checkbox element to the settings form.
  $settingsForm['fences']['my_checkbox'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable custom feature'),
    '#default_value' => FALSE,
  ];
}
