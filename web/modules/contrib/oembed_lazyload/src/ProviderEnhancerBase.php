<?php

namespace Drupal\oembed_lazyload;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\media\OEmbed\Resource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LazyloadPreprocessorBase.
 *
 * @package Drupal\oembed_lazyload
 */
abstract class ProviderEnhancerBase extends PluginBase implements ContainerFactoryPluginInterface, ProviderEnhancerInterface {

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory */
    $logger_channel_factory = $container->get('logger.factory');
    $instance->logger = $logger_channel_factory->get('oembed_lazyload');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'media/oembed.formatter',
      'oembed_lazyload/common',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder($url, Resource $resource, array $settings) {
    $placeholder = [
      '#theme' => 'oembed_lazyload_placeholder',
      '#url' => $url,
      '#title' => $resource->getTitle(),
      '#thumbnail' => $resource->getThumbnailUrl(),
      '#settings' => $settings,
    ];

    if ($provider = $resource->getProvider()) {
      $placeholder['#provider'] = $provider->getName();
    }
    else {
      $placeholder['#provider'] = '';
    }

    return $placeholder;
  }

  /**
   * {@inheritdoc}
   */
  public function getIframe(Url $url, Resource $resource, array $settings) {

    $iframe = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'data-src' => $url->toString(),
        'id' => Html::getUniqueId('oembed-iframe'),
        'scrolling' => FALSE,
        'width' => $settings['max_width'] ?: $resource->getWidth(),
        'height' => $settings['max_height'] ?: $resource->getHeight(),
        'class' => [
          'media-oembed-content',
          'oembed-lazyload__iframe',
          'oembed-lazyload__iframe--hidden',
        ],
      ],
    ];

    // An empty title attribute will disable title inheritance, so only
    // add it if the resource has a title.
    $title = $resource->getTitle();
    if ($title) {
      $iframe['#attributes']['title'] = $title;
    }
    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function alterOembedResponse($markup, array $options = []) {
    return $markup;
  }

}
