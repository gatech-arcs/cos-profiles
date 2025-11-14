<?php

namespace Drupal\ckeditor_heading_size\Config;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

/**
 * Service description.
 */
class AllowedHtmlOverride implements ConfigFactoryOverrideInterface {

  protected $settings;
  private ConfigFactoryInterface $configFactory;
  private bool $loadingOverrides = FALSE;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings = $config_factory->get('ckeditor_heading_size.settings')->get('size_options');
    $this->configFactory = $config_factory;
  }

  public function loadOverrides($names) {
    $overrides = [];

    if (\Drupal::state()->get('install_task') !== 'done') {
      return $overrides;
    }

    if ($this->loadingOverrides) {
      return $overrides;
    }
    // Are there any filter format configs? If so lets allow heading tags to
    // have our generated heading-size classes.
    $matches = preg_grep('/^filter\.format/', $names);
    if (!empty($matches)) {
      $this->loadingOverrides = TRUE;
      $heading_tags = _get_enabled_heading_tags();
      $heading_size_class_names = '';
      if (empty($this->settings)) {
        return $overrides;
      }
      foreach ($this->settings as $size_option) {
        $heading_size_class_names .= ' ' . _get_size_class_name($size_option);
      }
      foreach ($matches as $filter_format_config_name) {
        $filter_format_config = $this->configFactory->getEditable($filter_format_config_name)->getOriginal('filters', FALSE);
        if (!empty($filter_format_config['filter_html'])) {
          $original_allowed_html = $updated_allowed_html = $filter_format_config['filter_html']['settings']['allowed_html'];

          // Loop through the nodes of the allowed html and look for heading tags.
          $document = Html::load($original_allowed_html);
          foreach ($heading_tags as $tag) {
            $node_list = $document->getElementsByTagName($tag);
            if ($node_list->count()) {
              $node = $node_list->item(0);
              if ($node->hasAttribute('class')) {
                $classes = $node->getAttribute('class');
                if (!empty($classes)) {
                  // Find the relevant tag substring in the allowed_html.
                  $pattern = '/<'. $tag . '[^>]*class="([^"]*)"[^>]*>/i';
                  preg_match($pattern, $original_allowed_html, $matches);
                  // Add each individual heading-size class to the list of classes.
                  $allowed_heading_classes = $matches[1] . $heading_size_class_names;
                  $updated_heading_tag = str_replace($matches[1], $allowed_heading_classes, $matches[0]);
                  $updated_allowed_html = str_replace($matches[0], $updated_heading_tag, $updated_allowed_html);
                }

              }
              elseif (!$node->hasAttribute('class')) {
                // Add the class attribute to the string.
                preg_match('/<' . $tag . '[^>]*>/i', $original_allowed_html, $matches);
                $updated_heading_tag = sprintf('%s %s>', rtrim($matches[0], '>'), 'class="' . ltrim($heading_size_class_names, ' ') . '"');
                $updated_allowed_html = str_replace($matches[0], $updated_heading_tag, $updated_allowed_html);
              }
            }
          }
          if ($original_allowed_html !== $updated_allowed_html) {
            $overrides[$filter_format_config_name]['filters']['filter_html']['settings']['allowed_html'] = $updated_allowed_html;
          }
        }
      }
      $this->loadingOverrides = FALSE;
    }
    return $overrides;
  }

  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  public function getCacheSuffix() {
    return 'ckeditor_heading_size_override';
  }

}
