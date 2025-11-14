<?php

namespace Drupal\Tests\lb_direct_add\FunctionalJavascript;

/**
 * Contains test cases for popover functionality.
 *
 * @group lb_direct_add
 */
class LayoutBuilderDirectAddPopoverTest extends LayoutBuilderDirectAddTestBase {

  /**
   * Test case for popover functionality.
   */
  public function testPopover() {
    // We use the label for this version.
    $this->setUseLabel(1);

    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');

    $page = $this->getSession()->getPage();

    // The link with the default label should exist and be visible.
    $add_block = $page->findLink('Add block');
    static::assertNotNull($add_block);
    static::assertTrue($add_block->isVisible());

    // Change the label.
    $this->setLabel('Add a block');

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');

    // The default label should no longer exist.
    $add_block = $page->findLink('Add block');
    static::assertNull($add_block);

    // The link with the new label should exist and be visible.
    $add_block = $page->findLink('Add a block');
    static::assertNotNull($add_block);
    static::assertTrue($add_block->isVisible());

    // Basic block link should exist but not be visible.
    $basic_block = $page->findLink('Basic block');
    static::assertNotNull($basic_block);
    static::assertStringEndsWith(static::BASIC_BLOCK_HREF, $basic_block->getAttribute('href'));
    static::assertFalse($basic_block->isVisible());

    // More options link should exist but not be visible.
    $more = $page->findLink('More options');
    static::assertNotNull($more);
    static::assertStringEndsWith(static::CHOOSE_BLOCK_HREF, $more->getAttribute('href'));
    static::assertFalse($more->isVisible());

    // Click the link to show blocks.
    $add_block->click();

    // Basic block and more options links should be visible now.
    static::assertTrue($basic_block->isVisible());
    static::assertTrue($more->isVisible());

    // Click the link again to hide blocks.
    $add_block->click();

    // Basic block and more options links should not be visible now.
    static::assertFalse($basic_block->isVisible());
    static::assertFalse($more->isVisible());

  }

}
