<?php

namespace Drupal\oembed_lazyload\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\MediaType;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\Plugin\media\Source\OEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A formatter plugin that lazy loads oembed video content.
 *
 * @FieldFormatter(
 *   id = "lazyload_oembed",
 *   label = @Translation("Lazy load oEmbed video"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class LazyloadOEmbedFormatter extends FormatterBase {

  /**
   * The oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * The oembed lazyload iFrame URL helper service.
   *
   * @var \Drupal\oembed_lazyload\IframeUrlHelper
   */
  protected $oembedLazyloadIframeUrlHelper;

  /**
   * The OEmbed lazyload manager service.
   *
   * @var \Drupal\oembed_lazyload\ProviderEnhancerManager
   */
  protected $oembedLazyloadPluginManager;

  /**
   * The media settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->resourceFetcher = $container->get('media.oembed.resource_fetcher');
    $instance->urlResolver = $container->get('media.oembed.url_resolver');
    $instance->iFrameUrlHelper = $container->get('media.oembed.iframe_url_helper');
    $instance->oembedLazyloadIframeUrlHelper = $container->get('oembed_lazyload.iframe_url_helper');
    $instance->oembedLazyloadPluginManager = $container->get('oembed_lazyload');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    $instance->config = $config_factory->get('media.settings');

    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory */
    $logger_factory = $container->get('logger.factory');
    $instance->logger = $logger_factory->get('oembed_lazyload');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_width' => 0,
      'max_height' => 0,
      'strategy' => 'intersection_observer',
      'intersection_observer_margin' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'max_width' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum width'),
        '#default_value' => $this->getSetting('max_width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
      ],
      'max_height' => [
        '#type' => 'number',
        '#title' => $this->t('Maximum height'),
        '#default_value' => $this->getSetting('max_height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#field_suffix' => $this->t('pixels'),
        '#min' => 0,
      ],
      'strategy' => [
        '#type' => 'select',
        '#title' => $this->t('Lazy loading strategy'),
        '#default_value' => $this->getSetting('strategy'),
        '#options' => [
          'onclick' => $this->t('Load third party assets when the user activates a play button'),
          'intersection_observer' => $this->t('Load third party assets when it enters the viewport'),
        ],
      ],
      'intersection_observer_margin' => [
        '#type' => 'textfield',
        '#title' => $this->t('How far away from the viewport should content begin to load?'),
        '#description' => $this->t('This value must be defined as a number of pixels or percentage, IE. 20px or 4%.'),
        '#default_value' => $this->getSetting('intersection_observer_margin'),
        '#element_validate' => [
          [
            get_class($this),
            'validateIntersectionObserverMargin',
          ],
        ],
        '#states' => [
          'visible' => [
            'select[name="fields[field_media_oembed_video][settings_edit_form][settings][strategy]"]' => [
              'value' => 'intersection_observer',
            ],
          ],
        ],
      ],
      'onclick_info' => [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            'select[name="fields[field_media_oembed_video][settings_edit_form][settings][strategy]"]' => [
              'value' => 'onclick',
            ],
          ],
        ],
        'onclick' => [
          '#theme' => 'status_messages',
          '#message_list' => [
            'status' => [
              $this->t('This strategy can be useful if users are required to give explicit consent to load third party content.'),
            ],
            'warning' => [
              $this->t('Mobile users may have to click play twice if their browser does not allow auto-play (notably, Chrome + Safari).'),
            ],
          ],
        ],
      ],
      'intersection_observer_info' => [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            'select[name="fields[field_media_oembed_video][settings_edit_form][settings][strategy]"]' => [
              'value' => 'intersection_observer',
            ],
          ],
        ],
        'onclick' => [
          '#theme' => 'status_messages',
          '#message_list' => [
            'status' => [
              $this->t('This strategy can be useful if users do not have to give explicit consent to load third party content.'),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Validate callback for the intersection observer field.
   *
   * @param array $element
   *   The form being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public static function validateIntersectionObserverMargin(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value !== '' && !preg_match('/^\d+(px|%)$/', $value)) {
      $form_state->setError($element, 'The margin must be a positive integer specified in px or %.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('max_width') && $this->getSetting('max_height')) {
      $summary[] = $this->t('Maximum size: %max_width x %max_height pixels', [
        '%max_width' => $this->getSetting('max_width'),
        '%max_height' => $this->getSetting('max_height'),
      ]);
    }
    elseif ($this->getSetting('max_width')) {
      $summary[] = $this->t('Maximum width: %max_width pixels', [
        '%max_width' => $this->getSetting('max_width'),
      ]);
    }
    elseif ($this->getSetting('max_height')) {
      $summary[] = $this->t('Maximum height: %max_height pixels', [
        '%max_height' => $this->getSetting('max_height'),
      ]);
    }
    if ($this->getSetting('strategy') === 'onclick') {
      $summary[] = $this->t('Third party assets load when a user clicks a play button');
    }
    else {
      if ($margin = $this->getSetting('intersection_observer_margin')) {
        $summary[] = $this->t('Third party assets will begin to load @unit before entering the viewport.', [
          '@unit' => $margin,
        ]);
      }
      else {
        $summary[] = $this->t('Third party assets load as soon as an asset enters the viewport');
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $applicable = FALSE;
    if (parent::isApplicable($field_definition) && $field_definition->getTargetEntityTypeId() === 'media') {
      $media_type = $field_definition->getTargetBundle();

      if ($media_type) {
        $media_type = MediaType::load($media_type);
        $applicable = $media_type && $media_type->getSource() instanceof OEmbedInterface;
      }
    }
    return $applicable;
  }

  /**
   * {@inheritdoc}
   *
   * This is based on the internal OEmbedFormatter plugin.
   *
   * @see \Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter::viewElements
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      if (empty($value)) {
        continue;
      }

      try {
        $resource_url = $this->urlResolver->getResourceUrl($value, $settings['max_width'], $settings['max_height']);
        $resource = $this->resourceFetcher->fetchResource($resource_url);
      }
      catch (ResourceException $exception) {
        $this->logger->error('Could not retrieve the remote URL (@url).', ['@url' => $value]);
        continue;
      }
      $provider = $resource->getProvider();
      if ($provider === NULL) {
        $this->logger->error('Could not find the oembed provider.');
        continue;
      }
      /** @var \Drupal\oembed_lazyload\ProviderEnhancerInterface $enhancer */
      $enhancer = $this->oembedLazyloadPluginManager->getEnhancerForProvider($provider->getName());

      $query = [
        'url' => $value,
        'max_width' => $settings['max_width'],
        'max_height' => $settings['max_height'],
        'hash' => $this->iFrameUrlHelper->getHash($value, $settings['max_width'], $settings['max_height']),
        'oembed_lazyload' => 1,
        'provider' => $provider->getName(),
      ];

      $third_party_settings = $this->getThirdPartySettings($enhancer->getPluginDefinition()['provider']);
      if ($third_party_settings) {
        $query['options'] = $third_party_settings;
      }
      $query['oembed_lazyload_hash'] = $this->oembedLazyloadIframeUrlHelper->getHash($provider->getName(), $third_party_settings);

      $url = Url::fromRoute('media.oembed_iframe', [], [
        'query' => $query,
      ]);

      $domain = $this->config->get('iframe_domain');
      if ($domain) {
        $url->setOption('base_url', $domain);
      }

      $element[$delta] = [
        '#theme' => 'oembed_lazyload',
        '#attributes' => [
          'class' => [
            'oembed-lazyload',
            'oembed-lazyload--' . Html::getClass($provider->getName()),
          ],
        ],
        '#attached' => [
          'library' => $enhancer->getLibraries(),
        ],
        '#thumbnail' => $resource->getThumbnailUrl(),
        '#strategy' => $this->getSetting('strategy'),
        '#provider' => $provider->getName(),
        '#placeholder' => $enhancer->getPlaceholder($value, $resource, $settings),
        '#iframe' => $enhancer->getIframe($url, $resource, $settings),
      ];

      if ($this->getSetting('strategy') === 'intersection_observer') {
        $element[$delta]['#attached']['library'][] = 'oembed_lazyload/intersection-observer';
        $element[$delta]['#attached']['drupalSettings']['intersectionObserverMargin'] = $this->getSetting('intersection_observer_margin');
      }
      else {
        $element[$delta]['#attached']['library'][] = 'oembed_lazyload/onclick';
      }

      CacheableMetadata::createFromObject($resource)
        ->addCacheTags($this->config->getCacheTags())
        ->applyTo($element[$delta]);
    }

    return $element;
  }

}
