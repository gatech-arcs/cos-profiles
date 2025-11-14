<?php

namespace Drupal\fences\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The fences configuration form.
 */
class FencesConfigForm extends ConfigFormBase {

  /**
   * The theme registry service.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * The render cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The page cache, if it exists.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|null
   */
  protected $pageCache;

  /**
   * The dynamic page cache, if it exists.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|null
   */
  protected $dynamicPageCache;

  /**
   * FencesConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed configuration manager.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_render
   *   The render cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface|null $cache_page
   *   The page cache, if it exists.
   * @param \Drupal\Core\Cache\CacheBackendInterface|null $cache_dynamic_page_cache
   *   The dynamic page cache, if it exists.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, Registry $theme_registry, CacheBackendInterface $cache_render, CacheBackendInterface|null $cache_page, CacheBackendInterface|null $cache_dynamic_page_cache) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->themeRegistry = $theme_registry;
    $this->renderCache = $cache_render;
    $this->pageCache = $cache_page;
    $this->dynamicPageCache = $cache_dynamic_page_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('theme.registry'),
      $container->get('cache.render'),
      $container->has('cache.page') ? $container->get('cache.page') : NULL,
      $container->has('cache.dynamic_page_cache') ? $container->get('cache.dynamic_page_cache') : NULL
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fences.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fences_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fences.settings');

    $form['fences_field_template_override_all_themes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the field template for all themes.'),
      '#description' => $this->t('By default, the Fences module only overrides the field template (field.html.twig) for core themes. When this setting is enabled, Fences will override the field template for all themes (both core and config).'),
      '#default_value' => $config->get('fences_field_template_override_all_themes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('fences.settings');
    $config->set('fences_field_template_override_all_themes', $form_state->getValue('fences_field_template_override_all_themes'));
    $config->save();

    // Clear the theme registry caches:
    $this->themeRegistry->reset();
    // Clear select cache bins.
    $this->renderCache->deleteAll();
    if ($this->pageCache) {
      $this->pageCache->deleteAll();
    }
    if ($this->dynamicPageCache) {
      $this->dynamicPageCache->deleteAll();
    }
  }

}
