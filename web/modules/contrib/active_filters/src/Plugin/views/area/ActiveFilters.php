<?php

declare(strict_types=1);

namespace Drupal\active_filters\Plugin\views\area;

use Drupal\active_filters\ActiveFilter\ActiveFilterFactoryInterface;
use Drupal\active_filters\ActiveFilter\RenderArrayBuilderInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsArea;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area plugin to generate active filters from exposed filter values.
 */
#[ViewsArea('active_filters')]
final class ActiveFilters extends AreaPluginBase {

  /**
   * Rewrites.
   *
   * @var array<string, array<string, string>>
   */
  private array $rewrites = [];

  // phpcs:ignore Drupal.Commenting.FunctionComment.Missing
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ActiveFilterFactoryInterface $filterFactory,
    private readonly RenderArrayBuilderInterface $filterRenderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new ActiveFilters(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('active_filters.factory'),
      $container->get('active_filters.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();

    $options['empty'] = [
      'default' => TRUE,
    ];

    $options['title'] = [
      'default' => $this->t('Active Filters'),
    ];
    $options['hide_title'] = [
      'default' => FALSE,
    ];
    $options['grouped'] = [
      'default' => FALSE,
    ];
    $options['clear_text'] = [
      'default' => $this->t('Clear All Filters'),
    ];

    $options['filters']['contains'] = [];
    foreach ($this->exposedFilters() as $id => $filter) {
      $options['filters']['contains'][$id]['contains']['enable'] = [
        'default' => TRUE,
      ];
      $options['filters']['contains'][$id]['contains']['removable'] = [
        'default' => TRUE,
      ];
      $options['filters']['contains'][$id]['contains']['rewrite'] = [
        'default' => '',
      ];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(
    &$form,
    FormStateInterface $form_state,
  ): void {
    parent::buildOptionsForm($form, $form_state);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading Text'),
      '#default_value' => $this->options['title'],
      '#required' => TRUE,
    ];

    $form['hide_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Visually hide heading'),
      '#default_value' => $this->options['hide_title'],
    ];

    $form['grouped'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Group active filters by exposed filter'),
      '#description' => $this->t('Exposed filter labels will be output as the grouping label.'),
      '#default_value' => $this->options['grouped'],
    ];

    $form['clear_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clear All Button Text'),
      '#description' => $this->t('Leave empty to omit the clear all button.'),
      '#default_value' => $this->options['clear_text'],
    ];

    foreach ($this->exposedFilters() as $id => $filter) {
      $form['filters'][$id] = [
        '#type' => 'details',
        '#title' => $this->t("Advanced configuration for exposed filter '@filter'", [
          '@filter' => $id,
        ]),
        '#collapsed' => TRUE,
        '#collapsible' => TRUE,
      ];

      $form['filters'][$id]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Generate active filters'),
        '#default_value' => $this->options['filters'][$id]['enable'],
      ];

      $form['filters'][$id]['removable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Active filters can be removed individually'),
        '#default_value' => $this->options['filters'][$id]['removable'],
      ];

      $form['filters'][$id]['rewrite'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Rewrite active filter values'),
        '#description' => $this->t("Use the format of CURRENT|REPLACEMENT, one per line. Leave the replacement text blank to disable the display of an active filter value. For example:
<pre>
Current|Replacement
On|Yes
Off|No
Disable|
</pre>"),
        '#default_value' => $this->options['filters'][$id]['rewrite'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(
    &$form,
    FormStateInterface $form_state,
  ): void {
    parent::submitOptionsForm($form, $form_state);
    $filters = $this->exposedFilters();
    $this->options['filters'] = array_filter(
      $this->options['filters'],
      fn(string $id) => isset($filters[$id]),
      ARRAY_FILTER_USE_KEY,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE): array {
    if ($empty && !$this->options['empty']) {
      return [];
    }

    $active_filters = $this->generateActiveFilters();

    $this->getModuleHandler()->invokeAll('active_filters_alter', [
      &$active_filters,
      $this->view,
    ]);

    return $this->filterRenderer->activeFilters($active_filters, $this->options, $this->view);
  }

  /**
   * Get the views exposed filter handlers.
   *
   * @return array<string, \Drupal\views\Plugin\views\filter\FilterPluginBase>
   */
  private function exposedFilters(): array {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase[] $filters */
    $filters = $this->view->getDisplay()->getHandlers('filter');
    return array_filter(
      $filters,
      fn(FilterPluginBase $filter): bool => $filter->isExposed(),
    );
  }

  /**
   * Generate active filters from the view's currently selected exposed filters.
   *
   * @return array<int, \Drupal\active_filters\ActiveFilter\ActiveFilter|\Drupal\active_filters\ActiveFilter\ActiveFilterGroup>
   */
  private function generateActiveFilters(): array {
    $exposed_filters = $this->exposedFilters();
    $active_filters = [];

    // Merge exposed input with the view's default values, and remove any
    // empty values.
    $all_values = array_filter(
      $this->view->getExposedInput() + $this->view->exposed_raw_input,
      fn($value) => !($value === '' || $value === NULL),
    );
    foreach ($all_values as $name => $values) {
      $values = (array) $values;
      $total_values = \count($values);

      $exposed_filter = array_filter(
        $exposed_filters,
        fn(FilterPluginBase $filter) => $filter->options['expose']['identifier'] === $name,
      );
      $exposed_filter = reset($exposed_filter);

      // We can reasonably expect that $exposed_filter is not false, but check
      // for certainty before continuing.
      if (!$exposed_filter instanceof FilterPluginBase) {
        continue;
      }

      $options = $this->options['filters'][$exposed_filter->options['id']];

      if (!$options['enable']) {
        continue;
      }

      $active_filter_set = [];
      foreach ($values as $value) {
        // Default values set in views can be integers, so convert them to
        // strings.
        $value = (string) (\is_array($value) ? array_values($value)[0] : $value);

        // Do not generate active filters for empty values.
        if ($value === '') {
          continue;
        }

        // Rewrite the label if needed and do not generate active filters with
        // no labels.
        $label = $this->rewriteLabel(
          $exposed_filter->realField,
          $this->view->exposed_widgets[$name]['#options'][$value] ?? $value,
        );
        if (!$label) {
          continue;
        }

        $active_filter_set[] = $this->filterFactory->createActiveFilter(
          $label,
          $name,
          $value,
          $this->isRemovable($value, $options['removable'], $total_values, $exposed_filter),
          $options,
          $exposed_filter,
          $this->view,
        );
      }

      if ($this->options['grouped']) {
        $active_filters[] = $this->filterFactory->createActiveFilterGroup(
          $exposed_filter->options['expose']['label'] ?? $exposed_filter->pluginTitle(),
          $name,
          $active_filter_set,
          $options,
          $exposed_filter,
          $this->view,
        );
      }
      else {
        $active_filters = array_merge($active_filters, $active_filter_set);
      }
    }

    return $active_filters;
  }

  /**
   * Rewrite an active filter label.
   */
  private function rewriteLabel(
    string $filter_id,
    MarkupInterface|string $label,
  ): MarkupInterface|string {
    if (!isset($this->rewrites[$filter_id])) {
      $this->rewrites[$filter_id] = [];
      $rewrites = $this->options['filters'][$filter_id]['rewrite'] ?? '';
      if ($rewrites) {
        $rewrite_strings = explode("\r\n", $rewrites);
        foreach ($rewrite_strings as $rewrite_string) {
          [$original, $rewrite] = explode('|', $rewrite_string);
          $this->rewrites[$filter_id][$original] = $rewrite;
        }
      }
    }
    return $this->rewrites[$filter_id][(string) $label] ?? $label;
  }

  /**
   * Check if an active filter can be removed.
   */
  private function isRemovable(
    string $value,
    bool $removable,
    int $total_values,
    FilterPluginBase $filter,
  ): bool {
    if (!$removable) {
      return FALSE;
    }

    // If the field is required then it can only be removable when there is more
    // than one active filter.
    if (
      $total_values <= 1
      && $filter->options['expose']['required']
      && $filter->options['expose']['multiple']
    ) {
      return FALSE;
    }

    if (
      $filter instanceof BooleanOperator
      && $value !== 'All'
      && ($filter->options['group_info']['widget'] ?? '') === 'radios'
    ) {
      return (bool) ($filter->options['group_info']['optional'] ?? FALSE);
    }
    return $value !== 'All';
  }

}
