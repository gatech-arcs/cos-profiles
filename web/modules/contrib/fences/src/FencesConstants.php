<?php

namespace Drupal\fences;

/**
 * Used to globally store constant information for unified access.
 */
class FencesConstants {

  /**
   * Default third party settings of the fences formatter.
   */
  const DEFAULT_THIRD_PARTY_SETTINGS = [
    'fences_field_tag' => 'div',
    'fences_field_classes' => '',
    'fences_field_items_wrapper_tag' => TagManagerInterface::NO_MARKUP_VALUE,
    'fences_field_items_wrapper_classes' => '',
    'fences_field_item_tag' => 'div',
    'fences_field_item_classes' => '',
    'fences_label_tag' => 'div',
    'fences_label_classes' => '',
  ];

}
