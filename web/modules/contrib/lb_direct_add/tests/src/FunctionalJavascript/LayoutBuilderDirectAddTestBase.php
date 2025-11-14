<?php

namespace Drupal\Tests\lb_direct_add\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for layout builder direct add javascript tests.
 */
abstract class LayoutBuilderDirectAddTestBase extends WebDriverTestBase {
  /**
   * Path prefix for the field UI for the test bundle.
   */
  protected const FIELD_UI_PREFIX = '/admin/structure/types/manage/bundle_with_section_field';

  /**
   * Known href attribute for adding a basic block.
   */
  protected const BASIC_BLOCK_HREF = '/layout_builder/add/block/defaults/node.bundle_with_section_field.default/0/content/inline_block%3Abasic';

  /**
   * Known href attribute for adding a block through the core chooser.
   */
  protected const CHOOSE_BLOCK_HREF = '/layout_builder/choose/block/defaults/node.bundle_with_section_field.default/0/content';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'layout_builder',
    'block',
    'node',
    'contextual',
    'field_ui',
    'lb_direct_add',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Sets a new drop button display option.
   *
   * @param int $setting
   *   The new drop button display option.
   */
  protected function setUseLabel($setting) {
    $this->config('lb_direct_add.settings')
      ->set('use_label', $setting)
      ->save();
  }

  /**
   * Sets a new trigger label.
   *
   * @param string $setting
   *   The new label.
   */
  protected function setLabel($setting) {
    $this->config('lb_direct_add.settings')
      ->set('label', $setting)
      ->save();
  }

  /**
   * {@inheritdoc}
   *
   * Sets up the content types required for lb_direct_add tests.
   */
  protected function setUp() : void {
    parent::setUp();

    $this->createContentType([
      'type' => 'bundle_with_section_field',
      'new_revision' => TRUE,
    ]);

    /** @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display */
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('node', 'bundle_with_section_field');
    $display->enableLayoutBuilder()->setOverridable()->save();

    $bundle = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => 1,
    ]);
    $bundle->save();
    block_content_add_body_field($bundle->id());
  }

}
