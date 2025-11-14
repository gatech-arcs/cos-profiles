<?php

namespace Drupal\Tests\oembed_lazyload_youtube\Kernel;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\Resource;
use Drupal\oembed_lazyload_youtube\Plugin\oembed_lazyload\ProviderEnhancer\YoutubeEnhancer;

/**
 * Test cases pertaining to the YouTube enhancer plugin.
 *
 * @group oembed_lazyload_youtube
 */
class YoutubeEnhancerTest extends KernelTestBase {

  /**
   * The subject under test.
   *
   * @var \Drupal\oembed_lazyload\ProviderEnhancerInterface
   */
  protected $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $config = $this->createMock(Config::class);

    $config->method('get')->willReturn('https://example.com');
    $config_factory = $this->createMock(ConfigFactoryInterface::class);

    $config_factory->method('get')->willReturn($config);

    $this->container->set('config.factory', $config_factory);
    $this->instance = YoutubeEnhancer::create($this->container, [], 'youtube', []);
  }

  /**
   * Data provider for youtube video URLs.
   *
   * @see https://oembed.com/providers.json
   *
   * @return array
   *   An array of test URLs.
   */
  public static function urlProvider() {

    return [
      ['https://www.youtube.com/watch?v=usN-pKfw6Q8'],
      ['https://www.youtube.com/watch?v=usN-pKfw6Q8&otherqueryparams'],
      ['https://www.youtube.com/watch?v=usN-pKfw6Q8#anchor'],
      ['https://www.youtube.com/watch?v=usN-pKfw6Q8&otherqueryparams#anchor'],

      ['https://youtu.be/usN-pKfw6Q8'],
      ['https://youtu.be/usN-pKfw6Q8?queryparams=test'],
      ['https://youtu.be/usN-pKfw6Q8#anchor'],
      ['https://youtu.be/usN-pKfw6Q8?queryparams=test#anchor'],

      ['https://www.youtube.com/v/usN-pKfw6Q8'],
      ['https://www.youtube.com/v/usN-pKfw6Q8?queryparams'],
      ['https://www.youtube.com/v/usN-pKfw6Q8#anchor'],
      ['https://www.youtube.com/v/usN-pKfw6Q8?queryparams#anchor'],

      ['https://www.youtube.com/playlist?list=usN-pKfw6Q8'],
      ['https://www.youtube.com/playlist?list=usN-pKfw6Q8?queryparams'],
      ['https://www.youtube.com/playlist?list=usN-pKfw6Q8#anchor'],
      ['https://www.youtube.com/playlist?list=usN-pKfw6Q8?queryparams#anchor'],

      ['https://youtube.com/playlist?list=usN-pKfw6Q8'],
      ['https://youtube.com/playlist?list=usN-pKfw6Q8?queryparams'],
      ['https://youtube.com/playlist?list=usN-pKfw6Q8#anchor'],
      ['https://youtube.com/playlist?list=usN-pKfw6Q8?queryparams#anchor'],

      ['https://www.youtube.com/shorts/usN-pKfw6Q8'],
      ['https://www.youtube.com/shorts/usN-pKfw6Q8?queryparams'],
      ['https://www.youtube.com/shorts/usN-pKfw6Q8#anchor'],
      ['https://www.youtube.com/shorts/usN-pKfw6Q8?queryparams#anchor'],
    ];
  }

  /**
   * Tests that thumbnails are built appropriately.
   *
   * @param string $url
   *   The URL to test.
   *
   * @dataProvider urlProvider
   *
   * @covers \Drupal\oembed_lazyload_youtube\Plugin\oembed_lazyload\ProviderEnhancer\YoutubeEnhancer::getPlaceholder
   */
  public function testGetPlaceholder($url) {

    $resource = $this->createMock(Resource::class);

    $provider = $this->createMock(Provider::class);

    $provider->method('getName')
      ->willReturn('YouTube');

    $resource->method('getProvider')
      ->willReturn($provider);

    $resource->method('getTitle')
      ->willReturn('Mocked video');

    $resource->method('getThumbnailUrl')
      ->willReturn('test thumbnail url');

    $expected = [
      '#theme' => 'oembed_lazyload_placeholder',
      '#url' => $url,
      '#title' => 'Mocked video',
      '#thumbnail' => 'test thumbnail url',
      '#settings' => ['test' => 'value'],
      '#provider' => 'YouTube',
      '#third_party_settings' => ['embed_code' => 'usN-pKfw6Q8'],
    ];

    $actual = $this->instance->getPlaceholder($url, $resource, ['test' => 'value']);

    static::assertEquals($expected, $actual);
  }

  /**
   * Tests that the appropriate libraries are used.
   *
   * @covers \Drupal\oembed_lazyload_youtube\Plugin\oembed_lazyload\ProviderEnhancer\YoutubeEnhancer::getLibraries
   */
  public function testGetLibraries() {
    $expected = [
      'media/oembed.formatter',
      'oembed_lazyload/common',
      'oembed_lazyload_youtube/youtube',
    ];

    static::assertEquals($expected, $this->instance->getLibraries());
  }

  /**
   * Tests that the appropriate alteration is performed to oembed responses.
   *
   * @covers \Drupal\oembed_lazyload_youtube\Plugin\oembed_lazyload\ProviderEnhancer\YoutubeEnhancer::alterOembedResponse
   */
  public function testAlterOembedResponse() {
    $markup = '<iframe width="200" height="113" src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed', $this->instance->alterOembedResponse($markup));

    // Ensure that setting the autoplay setting works.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&autoplay=1', $this->instance->alterOembedResponse($markup, ['autoplay' => '1']));

    // Ensure that the modest branding setting works.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&modestbranding=1', $this->instance->alterOembedResponse($markup, ['modestbranding' => '1']));

    // Ensure that the enablejsapi setting works.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&enablejsapi=1', $this->instance->alterOembedResponse($markup, ['enablejsapi' => '1']));

    // Ensure that the origin setting does not set without enablejsapi.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed', $this->instance->alterOembedResponse($markup, ['origin' => '1']));

    // Ensure that the origin setting does set with enablejsapi.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&enablejsapi=1&origin=https://example.com', $this->instance->alterOembedResponse($markup, [
      'enablejsapi' => '1',
      'origin' => '1',
    ]));

    // Ensure that the hideinfo setting works.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&showinfo=0', $this->instance->alterOembedResponse($markup, ['hideinfo' => '1']));

    // Ensure that the rel setting works.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&rel=0', $this->instance->alterOembedResponse($markup, ['rel' => '1']));

    // Throw everything all together.
    static::assertStringContainsString('src="https://www.youtube.com/embed/usN-pKfw6Q8?feature=oembed&autoplay=1&modestbranding=1&enablejsapi=1&origin=https://example.com&showinfo=0&rel=0', $this->instance->alterOembedResponse($markup, [
      'autoplay' => '1',
      'modestbranding' => '1',
      'enablejsapi' => '1',
      'origin' => '1',
      'hideinfo' => '1',
      'rel' => '1',
    ]));
  }

}
