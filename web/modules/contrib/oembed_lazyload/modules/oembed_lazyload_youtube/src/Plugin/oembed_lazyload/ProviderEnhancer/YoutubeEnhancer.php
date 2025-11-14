<?php

namespace Drupal\oembed_lazyload_youtube\Plugin\oembed_lazyload\ProviderEnhancer;

use Drupal\media\OEmbed\Resource;
use Drupal\oembed_lazyload\ProviderEnhancerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An implementation that enhances YouTube content.
 *
 * @ProviderEnhancer(
 *   id = "youtube",
 *   label = "YouTube",
 *   providers = {
 *     "YouTube"
 *   }
 * )
 */
class YoutubeEnhancer extends ProviderEnhancerBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * Attempts to parse out an embed code from the provided url.
   *
   * @param string $url
   *   The url to attempt to parse.
   *
   * @return false|string|null
   *   The embed code, or a falsy value if the embed code cannot be parsed.
   */
  protected function getEmbedCode($url) {

    $embed_code = NULL;
    if (strpos($url, 'youtube.com/watch') !== FALSE) {
      $embed_code = substr($url, strpos($url, '?v=') + 3);
    }
    elseif (strpos($url, 'youtube.com/v/') !== FALSE) {
      $embed_code = substr($url, strpos($url, '/v/') + 3);
    }
    elseif (strpos($url, 'youtu.be/') !== FALSE) {
      $embed_code = substr($url, strpos($url, 'youtu.be/') + 9);
    }
    elseif (strpos($url, 'youtube.com/playlist?list=') !== FALSE) {
      $embed_code = substr($url, strpos($url, 'youtube.com/playlist?list=') + 26);
    }
    elseif (strpos($url, 'youtube.com/shorts/') !== FALSE) {
      $embed_code = substr($url, strpos($url, 'youtube.com/shorts/') + 19);
    }

    // Cut off any query parameters and fragments.
    return preg_replace('/[?&#].*/', '', $embed_code);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaceholder($url, Resource $resource, array $settings) {
    $placeholder = parent::getPlaceholder($url, $resource, $settings);
    if ($embed_code = $this->getEmbedCode($url)) {
      $third_party_settings = [
        'embed_code' => $embed_code,
      ];
      $placeholder['#third_party_settings'] = $third_party_settings;
    }
    return $placeholder;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = parent::getLibraries();
    $libraries[] = 'oembed_lazyload_youtube/youtube';
    return $libraries;
  }

  /**
   * {@inheritdoc}
   *
   * The $options array may contain:
   *   - modestbranding.
   */
  public function alterOembedResponse($markup, array $options = []) {
    $replace = '?feature=oembed';

    if (isset($options['autoplay']) && $options['autoplay'] === '1') {
      $replace .= '&autoplay=1';
    }

    if (isset($options['modestbranding']) && $options['modestbranding'] === '1') {
      $replace .= '&modestbranding=1';
    }

    if (isset($options['enablejsapi']) && $options['enablejsapi'] === '1') {
      $replace .= '&enablejsapi=1';

      if (isset($options['origin']) && $options['origin'] === '1') {
        $media_settings = $this->configFactory->get('media.settings');
        $iframe_domain = $media_settings->get('iframe_domain');
        if (empty($iframe_domain)) {
          $request = $this->requestStack->getCurrentRequest();
          if ($request) {
            $iframe_domain = $request->getSchemeAndHttpHost();
          }
        }
        if (!empty($iframe_domain)) {
          $replace .= "&origin=$iframe_domain";
        }
      }
    }

    if (isset($options['hideinfo']) && $options['hideinfo'] === '1') {
      $replace .= '&showinfo=0';
    }

    if (isset($options['rel']) && $options['rel'] === '1') {
      $replace .= '&rel=0';
    }

    return str_replace('?feature=oembed', $replace, $markup);
  }

}
