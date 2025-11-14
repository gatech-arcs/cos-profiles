<?php

namespace Drupal\Tests\oembed_lazyload\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\Resource;

/**
 * Test cases pertaining to the provider enhancer plugin base class.
 *
 * @coversClass \Drupal\oembed_lazyload\ProviderEnhancerBase
 *
 * @group oembed_lazyload
 */
class ProviderEnhancerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oembed_lazyload',
  ];

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

    /** @var \Drupal\oembed_lazyload\ProviderEnhancerManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('oembed_lazyload');
    $this->instance = $plugin_manager->getEnhancerForProvider('test');
  }

  /**
   * Test case for the default libraries inclusion.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerBase::getLibraries
   */
  public function testGetLibraries() {
    $expected = [
      'media/oembed.formatter',
      'oembed_lazyload/common',
    ];
    $actual = $this->instance->getLibraries();

    static::assertEquals($expected, $actual);
  }

  /**
   * Test case for the default placeholder generation with a known provider.
   */
  public function testGetPlaceholder() {
    $url = $this->createMock(Url::class);

    $thumbnail_url = $this->createMock(Url::class);

    $resource = $this->createMock(Resource::class);

    $provider = $this->createMock(Provider::class);

    $provider->method('getName')
      ->willReturn('test_provider');

    $resource->method('getProvider')
      ->willReturn($provider);

    $resource->method('getTitle')
      ->willReturn('Mocked video');

    $resource->method('getThumbnailUrl')
      ->willReturn($thumbnail_url);

    $expected = [
      '#theme' => 'oembed_lazyload_placeholder',
      '#url' => $url,
      '#title' => 'Mocked video',
      '#thumbnail' => $thumbnail_url,
      '#settings' => ['test' => 'value'],
      '#provider' => 'test_provider',
    ];

    $actual = $this->instance->getPlaceholder($url, $resource, ['test' => 'value']);

    static::assertSame($expected, $actual);
  }

  /**
   * Test case for the default placeholder generation with an unknown provider.
   */
  public function testGetPlaceholderNoProvider() {
    $url = $this->createMock(Url::class);

    $thumbnail_url = $this->createMock(Url::class);

    $resource = $this->createMock(Resource::class);

    $resource->method('getProvider')
      ->willReturn(NULL);

    $resource->method('getTitle')
      ->willReturn('Mocked video');

    $resource->method('getThumbnailUrl')
      ->willReturn($thumbnail_url);

    $expected = [
      '#theme' => 'oembed_lazyload_placeholder',
      '#url' => $url,
      '#title' => 'Mocked video',
      '#thumbnail' => $thumbnail_url,
      '#settings' => ['test' => 'value'],
      '#provider' => '',
    ];

    $actual = $this->instance->getPlaceholder($url, $resource, ['test' => 'value']);

    static::assertSame($expected, $actual);
  }

  /**
   * Test case for the default iframe generation with a title.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerBase::getIframe
   */
  public function testGetIframeWithTitle() {
    $url = $this->createMock(Url::class);

    $url->method('toString')
      ->willReturn('https://example.com');

    $resource = $this->createMock(Resource::class);

    $resource->method('getWidth')
      ->willReturn('550');
    $resource->method('getHeight')
      ->willReturn('310');
    $resource->method('getTitle')
      ->willReturn('Mocked video');

    $expected = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'data-src' => 'https://example.com',
        'scrolling' => FALSE,
        'width' => '550',
        'height' => '310',
        'class' => [
          'media-oembed-content',
          'oembed-lazyload__iframe',
          'oembed-lazyload__iframe--hidden',
        ],
        'title' => 'Mocked video',
        'id' => 'oembed-iframe',
      ],
    ];
    $actual = $this->instance->getIframe($url, $resource, [
      'max_width' => '550',
      'max_height' => '310',
    ]);
    static::assertEquals($expected, $actual);

    $expected['#attributes']['width'] = '440';
    $expected['#attributes']['height'] = '210';
    $expected['#attributes']['id'] = 'oembed-iframe--2';

    $actual = $this->instance->getIframe($url, $resource, [
      'max_width' => '440',
      'max_height' => '210',
    ]);
    static::assertEquals($expected, $actual);
  }

  /**
   * Test case for the default iframe generation without a title.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerBase::getIframe
   */
  public function testGetIframeWithoutTitle() {
    $url = $this->createMock(Url::class);

    $url->method('toString')
      ->willReturn('https://example.com');

    $resource = $this->createMock(Resource::class);

    $resource->method('getWidth')
      ->willReturn('550');
    $resource->method('getHeight')
      ->willReturn('310');
    $resource->method('getTitle')
      ->willReturn('');

    $expected = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'data-src' => 'https://example.com',
        'scrolling' => FALSE,
        'width' => '550',
        'height' => '310',
        'class' => [
          'media-oembed-content',
          'oembed-lazyload__iframe',
          'oembed-lazyload__iframe--hidden',
        ],
        'id' => 'oembed-iframe',
      ],
    ];
    $actual = $this->instance->getIframe($url, $resource, [
      'max_width' => '550',
      'max_height' => '310',
    ]);
    static::assertEquals($expected, $actual);

    $expected['#attributes']['width'] = '440';
    $expected['#attributes']['height'] = '210';
    $expected['#attributes']['id'] = 'oembed-iframe--2';

    $actual = $this->instance->getIframe($url, $resource, [
      'max_width' => '440',
      'max_height' => '210',
    ]);
    static::assertEquals($expected, $actual);
  }

  /**
   * Test case for the default oembed alter.
   *
   * @covers \Drupal\oembed_lazyload\ProviderEnhancerBase::alterOembedResponse
   */
  public function testAlterOembedResponse() {
    $expected = 'Test markup';
    $actual = $this->instance->alterOembedResponse($expected);

    static::assertEquals($expected, $actual);
  }

}
