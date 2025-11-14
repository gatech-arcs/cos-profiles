<?php

namespace Drupal\diff_plus\Plugin\diff\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\diff\Controller\PluginRevisionController;
use Drupal\diff\DiffLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Layout Builder diff layout.
 *
 * @DiffLayoutBuilder(
 *   id = "visual_inline_html5",
 *   label = @Translation("Visual Inline (HTML5)"),
 *   description = @Translation("HTML-5 compatible visual layout, displays revision comparison using the entity type view mode."),
 * )
 */
class VisualInlineHtml5DiffLayout extends DiffLayoutBase {

  /**
   * An array of "safe" HTML tags to pass to the XSS filter.
   */
  protected const HTML5_TAGS = [
    'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio', 'b', 'base',
    'bdi', 'bdo', 'blockquote', 'body', 'br', 'button', 'canvas', 'caption',
    'cite', 'code', 'col', 'colgroup', 'data', 'datalist', 'dd', 'del',
    'details', 'dfn', 'dialog', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset',
    'figcaption', 'figure', 'footer', 'form', 'head', 'header', 'hgroup',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'html', 'i', 'iframe', 'img',
    'input', 'ins', 'kbd', 'keygen', 'label', 'legend', 'li', 'link', 'main',
    'map', 'mark', 'menu', 'menuitem', 'meta', 'meter', 'nav', 'noscript',
    'object', 'ol', 'optgroup', 'option', 'output', 'p', 'param', 'picture',
    'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section',
    'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary',
    'sup', 'svg', 'table', 'tbody', 'td', 'template', 'textarea', 'tfoot',
    'th', 'thead', 'time', 'title', 'tr', 'track', 'u', 'ul', 'var', 'video',
    'wbr',
  ];

  /**
   * An array of "safe" SVG HTML tags to pass to the XSS filter.
   */
  protected const SVG_HTML5_TAGS = [
    // SVG.
    'svg',
    'altglyph',
    'altglyphdef',
    'altglyphitem',
    'animatecolor',
    'animatemotion',
    'animatetransform',
    'circle',
    'clippath',
    'defs',
    'desc',
    'ellipse',
    'filter',
    'font',
    'g',
    'glyph',
    'glyphref',
    'hkern',
    'image',
    'line',
    'lineargradient',
    'marker',
    'mask',
    'metadata',
    'mpath',
    'path',
    'pattern',
    'polygon',
    'polyline',
    'radialgradient',
    'rect',
    'stop',
    'switch',
    'symbol',
    'text',
    'textpath',
    'title',
    'tref',
    'tspan',
    'use',
    'view',
    'vkern',

    // SVG Filters.
    'feBlend',
    'feColorMatrix',
    'feComponentTransfer',
    'feComposite',
    'feConvolveMatrix',
    'feDiffuseLighting',
    'feDisplacementMap',
    'feDistantLight',
    'feFlood',
    'feFuncA',
    'feFuncB',
    'feFuncG',
    'feFuncR',
    'feGaussianBlur',
    'feMerge',
    'feMergeNode',
    'feMorphology',
    'feOffset',
    'fePointLight',
    'feSpecularLighting',
    'feSpotLight',
    'feTile',
    'feTurbulence',
  ];

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The html diff service.
   *
   * @var \HtmlDiffAdvancedInterface
   */
  protected $htmlDiff;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->htmlDiff = $container->get('diff.html_diff');
    $instance->htmlDiff->getConfig()->setPurifierEnabled(FALSE);
    $instance->configFactory = $container->get('config.factory');
    $instance->currentUser = $container->get('current_user');
    $instance->userData = $container->get('user.data');
    $instance->accountSwitcher = $container->get('account_switcher');
    $instance->requestStack = $container->get('request_stack');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * Gets the settings to render the diff with.
   *
   * @return array
   *   The settings to render the diff with.
   */
  protected function getDiffSettings() {
    $settings = $this->configFactory->get('diff_plus.settings')->get();
    if ($this->currentUser->hasPermission('personalize diff plus settings')) {
      $settings = array_replace_recursive(
        $settings,
        $this->userData->get('diff_plus', $this->currentUser->id(), 'settings') ?? []
      );
    }
    return $settings;
  }

  /**
   * Generates a single display mode option.
   *
   * @param string $machine_name
   *   The machine name of the view mode.
   * @param string $label
   *   The human readable name of the view mode.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being reviewed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $left_revision
   *   The left revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right_revision
   *   The right revision.
   *
   * @return array
   *   The display mode option.
   */
  protected function getDisplayModeOption($machine_name, $label, ContentEntityInterface $entity, ContentEntityInterface $left_revision, ContentEntityInterface $right_revision) {
    return [
      'title' => $label,
      'url' => PluginRevisionController::diffRoute($entity,
        $left_revision->getRevisionId(),
        $right_revision->getRevisionId(),
        $this->getPluginId(),
        ['view_mode' => $machine_name]
      ),
    ];
  }

  /**
   * Generates an array of display mode options.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being reviewed.
   * @param \Drupal\Core\Entity\ContentEntityInterface $left_revision
   *   The left revision.
   * @param \Drupal\Core\Entity\ContentEntityInterface $right_revision
   *   The right revision.
   * @param string $default_view_mode
   *   The  machine name of the default view mode.
   *
   * @return array
   *   The display mode options.
   */
  protected function getDisplayModeOptions(ContentEntityInterface $entity, ContentEntityInterface $left_revision, ContentEntityInterface $right_revision, $default_view_mode) {

    // Get all view modes for entity type.
    $view_modes = $this->entityDisplayRepository
      ->getViewModeOptionsByBundle($entity->getEntityTypeId(), $entity->bundle());

    $options = [
      $default_view_mode => [
        'title' => $view_modes[$default_view_mode],
        'url' => PluginRevisionController::diffRoute($entity,
        $left_revision->getRevisionId(),
        $right_revision->getRevisionId(),
        $this->getPluginId(),
        ['view_mode' => $default_view_mode]),
      ],
    ];
    unset($view_modes[$default_view_mode]);

    foreach ($view_modes as $view_mode => $view_mode_info) {
      $options[$view_mode] = [
        'title' => $view_mode_info,
        'url' => PluginRevisionController::diffRoute($entity,
          $left_revision->getRevisionId(),
          $right_revision->getRevisionId(),
          $this->getPluginId(),
          ['view_mode' => $view_mode]
        ),
      ];
    }

    return $options;
  }

  /**
   * Preprocesses the provided markup to provide a normalization layer.
   *
   * @param string $markup
   *   The markup to normalize.
   * @param array $settings
   *   The diff layout settings.
   *
   * @return string
   *   The normalized markup.
   */
  protected function preprocessMarkup($markup, array $settings) {

    // The diff library has issues with comments, so strip them.
    $dom = Html::load($markup);
    $xpath = new \DOMXPath($dom);
    foreach ($xpath->query('//comment()') as $element) {
      $element->parentNode->removeChild($element);
    }

    // Remove all script tags before Xss::filter can mess things up.
    $script_tags = $dom->getElementsByTagName('script');
    while ($script_tags->count()) {
      $script_tag = $script_tags->item(0);
      $script_tag->parentNode->removeChild($script_tag);
    }

    // Work around a bug in Xss::filter.
    if ($settings['visual_html5_preserve_inline_styles']) {
      $elements_with_inline_styles = $xpath->query('//*[@style]');
      foreach ($elements_with_inline_styles as $element_with_inline_styles) {
        $element_with_inline_styles->setAttribute(
          'data-diff-plus-style',
          $element_with_inline_styles->getAttribute('style')
        );
      }
    }

    return Html::serialize($dom);
  }

  /**
   * {@inheritdoc}
   */
  public function build(ContentEntityInterface $left_revision, ContentEntityInterface $right_revision, ContentEntityInterface $entity): array {

    $build = $this->buildRevisionsData($left_revision, $right_revision);
    $default_view_mode = $this->requestStack->getCurrentRequest()->query->get('view_mode') ?: 'default';

    $build['controls']['view_mode'] = [
      '#type' => 'item',
      '#title' => $this->t('View mode'),
      '#wrapper_attributes' => ['class' => 'diff-controls__item'],
      'filter' => [
        '#type' => 'operations',
        '#links' => $this->getDisplayModeOptions($entity, $left_revision, $right_revision, $default_view_mode),
      ],
    ];

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    // Trigger exclusion of interactive items like on preview.
    $left_revision->in_preview = TRUE;
    $right_revision->in_preview = TRUE;
    $left_view = $view_builder->view($left_revision, $default_view_mode);
    $right_view = $view_builder->view($right_revision, $default_view_mode);

    // Avoid render cache from being built.
    unset($left_view['#cache']);
    unset($right_view['#cache']);

    $settings = $this->getDiffSettings();

    $left = $this->preprocessMarkup($this->renderer->render($left_view), $settings);
    $right = $this->preprocessMarkup($this->renderer->render($right_view), $settings);

    $this->htmlDiff->setOldHtml($left);
    $this->htmlDiff->setNewHtml($right);
    $this->htmlDiff->build();

    $markup = Xss::filter($this->htmlDiff->getDifference(), array_merge(static::HTML5_TAGS, static::SVG_HTML5_TAGS));

    if ($settings['visual_html5_preserve_inline_styles']) {
      $dom = Html::load($markup);
      $xpath = new \DOMXPath($dom);
      $elements_with_inline_styles = $xpath->query('//*[@data-diff-plus-style]');
      foreach ($elements_with_inline_styles as $element_with_inline_styles) {
        $element_with_inline_styles->setAttribute(
          'style',
          $element_with_inline_styles->getAttribute('data-diff-plus-style')
        );
      }
      $markup = Html::serialize($dom);
    }

    // @todo Ask the security team about whether this is 100% safe.
    $build['diff']['#markup'] = Markup::create($markup);
    $build['#attached']['library'][] = 'diff/diff.visual_inline';

    return $build;
  }

}
