<?php

/**
 * @file
 * Post-update functions for CAS module.
 */

declare(strict_types=1);

/**
 * Implements hook_removed_post_updates().
 */
function cas_removed_post_updates(): array {
  return [
    'cas_post_update_8001' => '3.0.0',
    'cas_post_update_8002' => '3.0.0',
    'cas_post_update_8003' => '3.0.0',
  ];
}
