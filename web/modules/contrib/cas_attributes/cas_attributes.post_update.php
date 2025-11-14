<?php

/**
 * @file
 * Post update functions for CAS Attributes module.
 */

declare(strict_types=1);

/**
 * Don't serialize the mappings.
 */
function cas_attributes_post_update_unserialize_mappings(): void {
  $config = \Drupal::configFactory()->getEditable('cas_attributes.settings');
  $config
    ->set('field.mappings', array_filter(unserialize($config->get('field.mappings'), ['allowed_classes' => FALSE])))
    ->set('role.mappings', (array) unserialize($config->get('role.mappings'), ['allowed_classes' => FALSE]))
    ->save(TRUE);
}

/**
 * Set a default value for token_allowed_attributes.
 */
function cas_attributes_post_update_default_value_token_allowed_attributes(): void {
  $config = \Drupal::configFactory()->getEditable('cas_attributes.settings');
  $config
    ->set('token_allowed_attributes', [])
    ->save(TRUE);
}
