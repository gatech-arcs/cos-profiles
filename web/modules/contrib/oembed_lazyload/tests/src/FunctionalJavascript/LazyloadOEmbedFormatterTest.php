<?php

namespace Drupal\Tests\oembed_lazyload\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\media\Entity\Media;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\media_test_oembed\UrlResolver;
use Drupal\Tests\media\FunctionalJavascript\MediaJavascriptTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Test case for the lazyload embed formatter with the fallback provider.
 *
 * @group oembed_lazyload
 */
class LazyloadOEmbedFormatterTest extends MediaJavascriptTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'link',
    'media_test_oembed',
    'oembed_lazyload',
    'oembed_lazyload_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lockHttpClientToFixtures();

    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Prepares the prerequisite configuration for testing.
   *
   * @param array $settings
   *   The formatter settings to set up.
   */
  protected function prepareFormatterTest(array $settings) {
    $account = $this->drupalCreateUser(['view media']);
    $this->drupalLogin($account);

    $media_type = $this->createMediaType('oembed:video');

    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);

    EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => $media_type->id(),
      'mode' => 'full',
      'status' => TRUE,
    ])->removeComponent('thumbnail')
      ->setComponent($source_field->getName(), [
        'type' => 'lazyload_oembed',
        'settings' => $settings,
      ])
      ->save();

    $this->hijackProviderEndpoints();

    ResourceController::setResourceUrl('https://vimeo.com/7073899', $this->getFixturesDirectory() . '/video_vimeo.json');
    UrlResolver::setEndpointUrl('https://vimeo.com/7073899', 'video_vimeo.json');

    $entity = Media::create([
      'bundle' => $media_type->id(),
      $source_field->getName() => 'https://vimeo.com/7073899',
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());
  }

  /**
   * Tests the 'onclick' formatter strategy.
   */
  public function testViewElementsOnClick() {
    // Set up an 'onclick' strategy driven formatter.
    $this->prepareFormatterTest([
      'max_width' => 550,
      'max_height' => 310,
      'strategy' => 'onclick',
    ]);
    $assert = $this->assertSession();

    // Make sure the iframe has "src" swapped out with "data-src".
    $assert->elementNotExists('css', 'iframe[src]');
    $assert->elementExists('css', 'iframe[data-src]');
    $expected_attributes = [
      'url' => 'https%3A//vimeo.com/7073899',
      'max_width' => '550',
      'max_height' => '310',
      'oembed_lazyload' => '1',
      'provider' => 'Vimeo',
    ];
    foreach ($expected_attributes as $attribute => $value) {
      $assert->elementAttributeContains('css', 'iframe[data-src]', 'data-src', "$attribute=$value");
    }

    $button = $assert->elementExists('css', '.oembed-lazyload__button');

    // Scroll to the button so that it's in view.
    $this->getSession()->executeScript(<<<JS
(function(){
  document.querySelector('.oembed-lazyload').scrollIntoView(false);
})()
JS
    );

    // Give some time for any scroll listeners to erroneously fire.
    $this->getSession()->wait(1000);

    // Make sure nothing has changed.
    foreach ($expected_attributes as $attribute => $value) {
      $assert->elementAttributeContains('css', 'iframe[data-src]', 'data-src', "$attribute=$value");
    }

    // Do the swap.
    $button->click();
    $assert->elementNotExists('css', 'iframe[data-src]');
    $assert->elementExists('css', 'iframe[src]');

    // Make sure the button transitions to the loading state.
    static::assertNotNull($assert->waitForElement('css', '.oembed-lazyload__button--loading'));

    // @todo: Test the load complete event.
  }

  /**
   * Tests the 'intersection_observer' formatter strategy.
   */
  public function testViewElementsIntersectionObserver() {
    // Make the browser very short so the observer doesn't fire immediately.

    // Set up an 'intersection_observer' strategy driven formatter.
    $this->prepareFormatterTest([
      'max_width' => 550,
      'max_height' => 310,
      'strategy' => 'intersection_observer',
    ]);
    $assert = $this->assertSession();

    // Make sure the iframe has "src" swapped out with "data-src".
    $assert->elementNotExists('css', 'iframe[src]');
    $assert->elementExists('css', 'iframe[data-src]');
    $expected_attributes = [
      'url' => 'https%3A//vimeo.com/7073899',
      'max_width' => '550',
      'max_height' => '310',
      'oembed_lazyload' => '1',
      'provider' => 'Vimeo',
    ];
    foreach ($expected_attributes as $attribute => $value) {
      $assert->elementAttributeContains('css', 'iframe[data-src]', 'data-src', "$attribute=$value");
    }

    // Scroll to the button so that it's in view.
    $this->getSession()->executeScript(<<<JS
(function(){
  document.querySelector('.oembed-lazyload').scrollIntoView();
})()
JS
    );

    // Give some time for any scroll listeners to fire.
    $this->getSession()->wait(1000);

    // Make sure the button state is now loading.
    static::assertNotNull($assert->waitForElement('css', '.oembed-lazyload__button--loading'));

    // @todo: add in a test for load complete.
  }

}
