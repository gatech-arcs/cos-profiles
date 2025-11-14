<?php

namespace Drupal\Tests\lb_direct_add\FunctionalJavascript;

/**
 * Contains test cases for dropbutton functionality.
 *
 * @group lb_direct_add
 */
class LayoutBuilderDirectAddDropbuttonTest extends LayoutBuilderDirectAddTestBase {

  /**
   * Test case for dropbutton functionality.
   */
  public function testDropbutton() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');

    $page = $this->getSession()->getPage();

    // The basic block link should exist and be visible.
    $dropbutton_action = $page->findLink('Basic block');
    static::assertNotNull($dropbutton_action);
    static::assertStringEndsWith(static::BASIC_BLOCK_HREF, $dropbutton_action->getAttribute('href'));
    static::assertTrue($dropbutton_action->isVisible());

    // More options should exist, but not be visible.
    $dropbutton_more = $page->findLink('More options');
    static::assertNotNull($dropbutton_more);
    static::assertStringEndsWith(static::CHOOSE_BLOCK_HREF, $dropbutton_more->getAttribute('href'));
    static::assertFalse($dropbutton_more->isVisible());

    // Dropbutton should exist and be visible.
    $dropbutton_toggle = $page->findButton('List additional actions');
    static::assertNotNull($dropbutton_toggle);
    static::assertTrue($dropbutton_toggle->isVisible());

    // Click the dropbutton arrow.
    $dropbutton_toggle->click();
    // More options should be visible now.
    static::assertTrue($dropbutton_more->isVisible());

    // Click the dropbutton arrow again.
    $dropbutton_toggle->click();
    // More options should not be visible again.
    static::assertFalse($dropbutton_more->isVisible());

  }

}
