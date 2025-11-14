<?php

namespace Drupal\Tests\oembed_lazyload\Functional;

/**
 * Test cases pertaining to CKEditor5.
 *
 * @group oembed_lazyload
 */
class CKEditor5Test extends CKEditor5TestBase {

  /**
   * Tests that the proper libraries are included when drupalMedia is enabled.
   */
  public function testLibraryInclusion() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/node/add/page');

    $assert = $this->assertSession();

    // Ensure that assets are not included by default.
    $assert->elementNotExists('css', 'link[href*="/oembed_lazyload/css/oembed-lazyload.css"]');
    $assert->elementNotExists('css', 'link[href*="/oembed_lazyload/css/oembed-lazyload-style.css"]');

    $this->createDrupalMediaTextFormat();
    $this->drupalGet('/node/add/page');

    // Ensure that the proper assets are included when drupalMedia is.
    $assert->elementExists('css', 'link[href*="/oembed_lazyload/css/oembed-lazyload.css"]');
    $assert->elementExists('css', 'link[href*="/oembed_lazyload/css/oembed-lazyload-style.css"]');
  }

}
