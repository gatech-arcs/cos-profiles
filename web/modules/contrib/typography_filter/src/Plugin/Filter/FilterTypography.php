<?php

namespace Drupal\typography_filter\Plugin\Filter;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\typography_filter\Services\TypographyFilterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide the filter_typography text filter.
 */
#[Filter(
  id: "filter_typography",
  title: new TranslatableMarkup("Improve typography"),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  description: new TranslatableMarkup("Use <a href='https://github.com/jolicode/JoliTypo'>JoliTypo</a> to automatically fix typography by dealing with spaces around punctuation marks, replacing quotes, hyphenating, etc."),
  settings: [],
)]
class FilterTypography extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The module handle service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * The 'typography_filter' manager.
   *
   * @var \Drupal\typography_filter\Services\TypographyFilterManagerInterface
   */
  protected TypographyFilterManagerInterface $typographyFilterManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->themeManager = $container->get('theme.manager');
    $instance->typographyFilterManager = $container->get('typography_filter.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['fixers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available fixers'),
      '#description' => $this->t('Please select the fixers you need to apply.'),
      '#options' => TypographyFilterManagerInterface::class::DEFAULT_RULES,
      '#default_value' => $this->settings['fixers'],
      '#element_validate' => [[static::class, 'validateOptions']],
    ];

    return $form;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The checkboxes form an element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateOptions(array &$element, FormStateInterface $form_state): void {
    // Filters the #value property so only selected values appear in the
    // config.
    $form_state->setValueForElement($element, array_filter($element['#value']));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $settings = [
      'fixers' => [],
      'locale' => $langcode,
    ];
    // Get enabled fixers.
    if (!empty($this->settings['fixers'])) {
      $global_filters = array_intersect_key(TypographyFilterManagerInterface::class::DEFAULT_RULES,
            array_flip($this->settings['fixers']));
      $settings['fixers'] = array_merge($settings['fixers'], $global_filters);
    }

    // Get updated settings from hooks.
    $this->moduleHandler->alter('typography_filter_settings', $settings, $langcode);
    $this->themeManager->alter('typography_filter_settings', $settings, $langcode);

    if (!empty($settings['fixers'])) {
      $text = $this->typographyFilterManager->fix($text, $settings['fixers'], $langcode);
    }

    return new FilterProcessResult($text);
  }

}
