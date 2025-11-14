<?php

namespace Drupal\diff_plus\Plugin\diff\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\diff\DiffLayoutBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Layout Builder diff layout.
 *
 * @DiffLayoutBuilder(
 *   id = "raw_html",
 *   label = @Translation("Raw HTML"),
 *   description = @Translation("Raw HTML differential."),
 * )
 */
class RawHtmlDiffLayout extends DiffLayoutBase {

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->currentUser = $container->get('current_user');
    $instance->userData = $container->get('user.data');
    $instance->accountSwitcher = $container->get('account_switcher');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Gets the settings to render the diff with.
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
   * Renders a revision with the provided view builder.
   *
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder
   *   The view builder to render the revision with.
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   The revision to render.
   * @param array $settings
   *   The settings to render the revision with.
   *
   * @return string
   *   The markup of the rendered revision.
   *
   * @throws \Exception
   *   Should never happen.
   */
  protected function renderRevision(EntityViewBuilderInterface $view_builder, ContentEntityInterface $revision, array $settings) {
    Html::resetSeenIds();

    if ($settings['raw_html_render_anonymously']) {
      $this->accountSwitcher->switchTo(new AnonymousUserSession());
    }

    $render_array = $view_builder->view($revision);
    $markup = $this->renderer->render($render_array);

    $dom = Html::load($markup);
    $xpath = new \DOMXPath($dom);
    if ($settings['raw_html_strip_js_view_dom_id']) {
      foreach ($xpath->query('//*[contains(@class, "js-view-dom-id-")]') as $element) {
        $class = $element->attributes['class']->value;
        $element->setAttribute('class', preg_replace('/\s*js-view-dom-id-.+(\s*|$)/', '', $class));
      }
    }
    if ($settings['raw_html_strip_contextual_links']) {
      foreach ($xpath->query('//*[@data-contextual-id]') as $element) {
        $element->parentNode->removeChild($element);
      }
    }

    if ($settings['raw_html_strip_comments']) {
      foreach ($xpath->query('//comment()') as $element) {
        $element->parentNode->removeChild($element);
      }
    }

    $markup = Html::serialize($dom);

    if ($settings['raw_html_render_anonymously']) {
      $this->accountSwitcher->switchBack();
    }

    return $markup;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Should never happen.
   */
  public function build(ContentEntityInterface $left_revision, ContentEntityInterface $right_revision, ContentEntityInterface $entity): array {

    $build = $this->buildRevisionsData($left_revision, $right_revision);
    $this->renderer->addCacheableDependency($build, $this->configFactory->get('diff_plus.settings'));
    $this->renderer->addCacheableDependency($build, $this->currentUser);
    $build['preview'] = [
      '#type' => 'container',
      '#attributes' => [
        'data-diff-plus-preview' => '',
      ],
    ];

    $view_builder = $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId());

    $settings = $this->getDiffSettings();

    $build['#attached']['drupalSettings']['diff_plus'] = [
      'diff_name' => "{$entity->label()}.html",
      'left_revision' => $this->renderRevision($view_builder, $left_revision, $settings),
      'right_revision' => $this->renderRevision($view_builder, $right_revision, $settings),
      'beautify_options' => [
        'indent_size' => $settings['raw_html_indent_size'],
        'preserve_newlines' => $settings['raw_html_preserve_newlines'],
        'wrap_line_length' => $settings['raw_html_wrap_line_length'],
      ],
      'ui_options' => [
        'drawFileList' => FALSE,
        'fileListToggle' => FALSE,
        'fileListStartVisible' => FALSE,
        'fileContentToggle' => FALSE,
        'matching' => 'lines',
        'outputFormat' => 'side-by-side',
        'synchronisedScroll' => TRUE,
        'highlight' => TRUE,
        'renderNothingWhenEmpty' => FALSE,
      ],
      'highlight_style' => $settings['raw_html_highlight_style'],
    ];

    $build['#attached']['library'][] = 'diff_plus/raw-html';

    return $build;
  }

}
