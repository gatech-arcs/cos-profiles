<?php

namespace Drupal\lb_direct_add;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Implements preRender for layout builder.
 */
class LayoutBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Alters layout builder to use dropbuttons to add custom blocks.
   */
  public static function preRender($element) {
    $inline_blocks_category = (string) t('Inline blocks');
    $lb = &$element['layout_builder'];

    $section_storage = $element['#section_storage'];

    // @todo Is there a better way to find the delta value?
    $delta = 0;

    $contexts = \Drupal::service('context.repository')->getAvailableContexts();

    $storage_type = $section_storage->getStorageType();
    $storage_id = $section_storage->getStorageId();
    $block_manager = \Drupal::getContainer()->get('plugin.manager.block');

    // Get restriction definitions if
    // 'layout_builder_restrictions' is installed.
    $restriction_plugins = NULL;
    $layout_builder_restrictions_manager = NULL;
    if (\Drupal::moduleHandler()->moduleExists('layout_builder_restrictions')) {
      $layout_builder_restrictions_manager = \Drupal::service('plugin.manager.layout_builder_restriction');
      // Retrieve defined Layout Builder Restrictions plugins.
      $restriction_plugins = $layout_builder_restrictions_manager->getSortedPlugins();
    }

    // Get config so that we can check it later.
    $config = \Drupal::service('config.factory')->get('lb_direct_add.settings');
    $element['#attached']['library'][] = 'lb_direct_add/direct_add';

    $attributes = [
      'class' => [
        'use-ajax',
        'js-layout-builder-block-link',
      ],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
    ];

    // Check if the current user can access the "More options" link.
    $access_more_options = \Drupal::currentUser()->hasPermission('access layout builder direct add more options');

    // Loop through each section.
    foreach (Element::children($lb) as $section) {
      if (isset($lb[$section]['layout-builder__section'])) {
        // Loop through each region.
        foreach (Element::children($lb[$section]['layout-builder__section']) as $region) {
          // Based on Drupal\layout_builder\Controller\ChooseBlockController::inlineBlockList()
          $definitions = $block_manager->getFilteredDefinitions('layout_builder', $contexts, [
            'section_storage' => $section_storage,
            'region' => $region,
            'list' => 'inline_blocks',
          ]);

          $blocks = $block_manager->getGroupedDefinitions($definitions);

          $links = [];
          if (isset($blocks[$inline_blocks_category])) {
            // Remove layout_builder_restrictions
            // filtered blocks, if applicable.
            if ($restriction_plugins && $layout_builder_restrictions_manager) {
              foreach (array_keys($restriction_plugins) as $id) {
                $plugin = $layout_builder_restrictions_manager->createInstance($id);
                $allowed_inline_blocks = $plugin->inlineBlocksAllowedinContext($section_storage, $delta, $region);

                // Loop through links and remove
                // those for disallowed inline block types.
                foreach ($blocks[$inline_blocks_category] as $block_id => $block) {
                  if (!in_array($block_id, $allowed_inline_blocks)) {
                    unset($blocks[$inline_blocks_category][$block_id]);
                  }
                }
              }
            }

            foreach ($blocks[$inline_blocks_category] as $block_id => $block) {
              $link = [
                'title' => t('<span class="visually-hidden">Add </span>@label', [
                  '@label' => $block['admin_label'],
                ]),
                'url' => Url::fromRoute('layout_builder.add_block',
                  [
                    'section_storage_type' => $section_storage->getStorageType(),
                    'section_storage' => $section_storage->getStorageId(),
                    'delta' => $delta,
                    'region' => $region,
                    'plugin_id' => $block_id,
                  ]
                ),
                'attributes' => $attributes,
              ];

              $links[] = $link;
            }
          }

          // Add the "More options" link if the user has permission.
          if ($access_more_options) {
            $links['other'] = [
              'title' => t('More<span class="visually-hidden"> options</span>...'),
              'url' => Url::fromRoute('layout_builder.choose_block',
                [
                  'section_storage_type' => $storage_type,
                  'section_storage' => $storage_id,
                  'delta' => $delta,
                  'region' => $region,
                ],
                [
                  'attributes' => [
                    'class' => [
                      'use-ajax',
                      'layout-builder__direct-add__more-link',
                    ],
                    'data-dialog-type' => 'dialog',
                    'data-dialog-renderer' => 'off_canvas',
                  ],
                ]
              ),
            ];
          }

          if (isset($lb[$section]['layout-builder__section'][$region]['layout_builder_add_block'])) {

            if ($config->get('use_label')) {
              static::addLinks($lb[$section]['layout-builder__section'][$region]['layout_builder_add_block'], $links);
            }
            else {
              static::addDropbutton($lb[$section]['layout-builder__section'][$region]['layout_builder_add_block'], $links);
            }

            // @todo Consider using the existing link as trigger for popover menu option.
            unset($lb[$section]['layout-builder__section'][$region]['layout_builder_add_block']['link']);
          }
        }

        // @todo Is there a better way to find the delta value?
        $delta++;
      }
    }

    return $element;
  }

  /**
   * Render the dropbutton element.
   */
  protected static function addDropbutton(array &$add_block, array $links) {
    $add_block['button'] = [
      '#type' => 'dropbutton',
      '#links' => $links,
    ];
  }

  /**
   * Render a trigger link and a list of links.
   */
  protected static function addLinks(array &$add_block, array $links) {
    $config = \Drupal::service('config.factory')->get('lb_direct_add.settings');
    $add_block['list_toggle'] = [
      '#type' => 'link',
      '#title' => $config->get('label'),
      '#url' => Url::fromRoute('<current>'),
      '#attributes' => [
        'class' => ['layout-builder__direct-add__toggle'],
      ],
    ];
    $add_block['list'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'layout-builder__direct-add__list',
        ],
      ],
    ];
    $add_block['list']['links'] = [
      '#theme' => 'links',
      '#links' => $links,
    ];
  }

}
