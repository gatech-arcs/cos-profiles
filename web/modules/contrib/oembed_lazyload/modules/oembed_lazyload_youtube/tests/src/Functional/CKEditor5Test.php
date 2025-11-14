<?php

namespace Drupal\Tests\oembed_lazyload_youtube\Functional;

use Drupal\Tests\oembed_lazyload\Functional\CKEditor5TestBase;

/**
 * Test cases pertaining to CKEditor5.
 *
 * @group oembed_lazyload_youtube
 */
class CKEditor5Test extends CKEditor5TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'node',
    'oembed_lazyload_youtube',
  ];

  /**
   * Tests that the proper libraries are included when drupalMedia is enabled.
   */
  public function testLibraryInclusion() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/node/add/page');

    $assert = $this->assertSession();

    // Ensure that assets are not included by default.
    $assert->elementNotExists('css', 'link[href*="/oembed_lazyload/modules/oembed_lazyload_youtube/css/youtube.css"]');

    $this->createDrupalMediaTextFormat();
    $this->drupalGet('/node/add/page');

    // Ensure that the proper assets are included when drupalMedia is.
    $assert->elementExists('css', 'link[href*="/oembed_lazyload/modules/oembed_lazyload_youtube/css/youtube.css"]');
  }

}
