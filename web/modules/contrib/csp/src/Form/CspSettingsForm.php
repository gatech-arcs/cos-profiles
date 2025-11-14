<?php

namespace Drupal\csp\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ToConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\csp\Csp;
use Drupal\csp\LibraryPolicyBuilder;
use Drupal\csp\ReportingHandlerPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Form for editing Content Security Policy module settings.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 */
class CspSettingsForm extends ConfigFormBase {

  /**
   * The Library Policy Builder service.
   *
   * @var \Drupal\csp\LibraryPolicyBuilder
   */
  private LibraryPolicyBuilder $libraryPolicyBuilder;

  /**
   * The Reporting Handler Plugin Manager service.
   *
   * @var \Drupal\csp\ReportingHandlerPluginManager
   */
  private ReportingHandlerPluginManager $reportingHandlerPluginManager;

  /**
   * A map of keywords and the directives which they are valid for.
   *
   * @var array<string, string[]>
   */
  private static array $keywordDirectiveMap = [
    // A violation’s sample will be populated with the first 40 characters of an
    // inline script, event handler, or style that caused a violation.
    // Violations which stem from an external file will not include a sample in
    // the violation report.
    // @see https://www.w3.org/TR/CSP3/#framework-violation
    'report-sample' => ['default-src', 'script-src', 'script-src-attr', 'script-src-elem', 'style-src', 'style-src-attr', 'style-src-elem'],
    'inline-speculation-rules' => ['default-src', 'script-src'],
    // Unsafe-hashes only applies to inline attributes.
    'unsafe-hashes' => ['default-src', 'script-src', 'script-src-attr', 'style-src', 'style-src-attr'],
    'unsafe-inline' => ['default-src', 'script-src', 'script-src-attr', 'script-src-elem', 'style-src', 'style-src-attr', 'style-src-elem'],
    // Since "unsafe-eval" acts as a global page flag, script-src-attr and
    // script-src-elem are not used when performing this check, instead
    // script-src (or it’s fallback directive) is always used.
    // @see https://www.w3.org/TR/CSP3/#directive-script-src
    'unsafe-eval' => ['default-src', 'script-src'],
    'wasm-unsafe-eval' => ['default-src', 'script-src'],
    'unsafe-allow-redirects' => ['navigate-to'],
    // 'strict-dynamic' requires adding a hash or nonce to scripts, so is not
    // included as a configurable option.
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'csp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'csp.settings',
    ];
  }

  /**
   * Constructs a \Drupal\csp\Form\CspSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The TypedConfigManager service.
   * @param \Drupal\csp\LibraryPolicyBuilder $libraryPolicyBuilder
   *   The Library Policy Builder service.
   * @param \Drupal\csp\ReportingHandlerPluginManager $reportingHandlerPluginManager
   *   The Reporting Handler Plugin Manger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    LibraryPolicyBuilder $libraryPolicyBuilder,
    ReportingHandlerPluginManager $reportingHandlerPluginManager,
    MessengerInterface $messenger,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->libraryPolicyBuilder = $libraryPolicyBuilder;
    $this->reportingHandlerPluginManager = $reportingHandlerPluginManager;
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('csp.library_policy_builder'),
      $container->get('plugin.manager.csp_reporting_handler'),
      $container->get('messenger')
    );
  }

  /**
   * Get the directives that should be configurable.
   *
   * @return string[]
   *   An array of directive names.
   */
  private function getConfigurableDirectives(): array {
    // Exclude some directives
    // - Reporting directives are handled by plugins.
    // - Other directives were removed from spec (see Csp class for details).
    $directives = array_diff(
      Csp::getDirectiveNames(),
      [
        'report-uri',
        'report-to',
        'navigate-to',
        'plugin-types',
        'referrer',
        'require-sri-for',
      ]
    );

    return $directives;
  }

  /**
   * Get the valid keyword options for a directive.
   *
   * @param string $directive
   *   The directive to get keywords for.
   *
   * @return string[]
   *   An array of keywords.
   */
  private function getKeywordOptions(string $directive): array {
    return array_keys(array_filter(
      self::$keywordDirectiveMap,
      function ($directives) use ($directive) {
        return in_array($directive, $directives);
      }
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $reportingHandlerPluginDefinitions = $this->reportingHandlerPluginManager->getDefinitions();
    $config = $this->config('csp.settings');
    $autoDirectives = $this->libraryPolicyBuilder->getSources();

    $form['#attached']['library'][] = 'csp/admin';

    $form['policies'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Policies'),
      '#default_tab' => 'edit-report-only',
    ];

    $directiveNames = $this->getConfigurableDirectives();
    $enforceOnlyDirectives = [
      // @see https://w3c.github.io/webappsec-upgrade-insecure-requests/#delivery
      'upgrade-insecure-requests',
      // @see https://www.w3.org/TR/CSP/#directive-sandbox
      'sandbox',
    ];

    $policyTypes = [
      'report-only' => $this->t('Report Only'),
      'enforce' => $this->t('Enforced'),
    ];
    foreach ($policyTypes as $policyTypeKey => $policyTypeName) {
      $form[$policyTypeKey] = [
        '#type' => 'details',
        '#title' => $policyTypeName,
        '#group' => 'policies',
        '#tree' => TRUE,
      ];

      $form[$policyTypeKey]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Enable '@type'", ['@type' => $policyTypeName]),
        '#config_target' => new ConfigTarget(
          'csp.settings',
          $policyTypeKey . '.enable',
        ),
      ];

      $form[$policyTypeKey]['directives'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Directives'),
        '#description_display' => 'before',
        '#tree' => TRUE,
      ];

      foreach ($directiveNames as $directiveName) {
        $directiveSchema = Csp::getDirectiveSchema($directiveName);

        $form[$policyTypeKey]['directives'][$directiveName] = [
          '#type' => 'container',
          '#access' => $policyTypeKey == 'enforce' || !in_array($directiveName, $enforceOnlyDirectives),
          '#config_target' => new ConfigTarget(
            'csp.settings',
            $policyTypeKey . '.directives.' . $directiveName,
            toConfig: match($directiveSchema) {
              Csp::DIRECTIVE_SCHEMA_BOOLEAN => self::booleanToConfig(...),
              Csp::DIRECTIVE_SCHEMA_TOKEN_LIST,
              Csp::DIRECTIVE_SCHEMA_OPTIONAL_TOKEN_LIST => self::tokenListToConfig(...),
              Csp::DIRECTIVE_SCHEMA_ALLOW_BLOCK => self::allowBlockToConfig(...),
              Csp::DIRECTIVE_SCHEMA_SOURCE_LIST,
              Csp::DIRECTIVE_SCHEMA_ANCESTOR_SOURCE_LIST => self::sourceListToConfig(...),
              Csp::DIRECTIVE_SCHEMA_TRUSTED_TYPES =>  self::trustedTypesToConfig(...),
              Csp::DIRECTIVE_SCHEMA_TRUSTED_TYPES_SINK_GROUPS => self::sinkGroupsToConfig(...),
              default => self::directiveToConfig(...),
            },
          ),
        ];

        $form[$policyTypeKey]['directives'][$directiveName]['enable'] = [
          '#type' => 'checkbox',
          '#title' => $directiveName,
        ];
        if (!empty($autoDirectives[$directiveName])) {
          $form[$policyTypeKey]['directives'][$directiveName]['enable']['#title'] .= ' <span class="csp-directive-auto">auto</span>';
        }

        if ($config->get($policyTypeKey)) {
          // Csp::DIRECTIVE_SCHEMA_OPTIONAL_TOKEN_LIST may be an empty array,
          // so is_null() must be used instead of empty().
          // Directives which cannot be empty should not be present in config.
          // (e.g. boolean directives should only be present if TRUE).
          $form[$policyTypeKey]['directives'][$directiveName]['enable']['#default_value'] = !is_null($config->get($policyTypeKey . '.directives.' . $directiveName));
        }
        else {
          // Directives to enable by default (with 'self').
          if (
            in_array($directiveName, ['script-src', 'script-src-attr', 'script-src-elem', 'style-src', 'style-src-attr', 'style-src-elem', 'frame-ancestors'])
            ||
            isset($autoDirectives[$directiveName])
          ) {
            $form[$policyTypeKey]['directives'][$directiveName]['enable']['#default_value'] = TRUE;
          }
        }

        $form[$policyTypeKey]['directives'][$directiveName]['options'] = [
          '#type' => 'container',
          '#states' => [
            'visible' => [
              ':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][enable]"]' => ['checked' => TRUE],
            ],
          ],
        ];

        if (!in_array($directiveSchema, [
          Csp::DIRECTIVE_SCHEMA_SOURCE_LIST,
          Csp::DIRECTIVE_SCHEMA_ANCESTOR_SOURCE_LIST,
        ])) {
          continue;
        }

        $sourceListBase = $config->get($policyTypeKey . '.directives.' . $directiveName . '.base');
        $form[$policyTypeKey]['directives'][$directiveName]['options']['base'] = [
          '#type' => 'radios',
          '#parents' => [$policyTypeKey, 'directives', $directiveName, 'base'],
          '#options' => [
            'self' => "Self",
            'none' => "None",
            'any' => "Any",
            '' => '<em>n/a</em>',
          ],
          '#default_value' => $sourceListBase ?? 'self',
        ];
        // Auto sources make a directive required, so remove the 'none' option.
        if (isset($autoDirectives[$directiveName])) {
          unset($form[$policyTypeKey]['directives'][$directiveName]['options']['base']['#options']['none']);
        }

        // Keywords are only applicable to serialized-source-list directives.
        if ($directiveSchema == Csp::DIRECTIVE_SCHEMA_SOURCE_LIST) {
          // States currently don't work on checkboxes elements, so need to be
          // applied to a wrapper.
          // @see https://www.drupal.org/project/drupal/issues/994360
          $form[$policyTypeKey]['directives'][$directiveName]['options']['flags_wrapper'] = [
            '#type' => 'container',
            '#states' => [
              'visible' => [
                [':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][base]"]' => ['!value' => 'none']],
              ],
            ],
          ];

          $keywordOptions = self::getKeywordOptions($directiveName);
          $keywordOptions = array_combine(
            $keywordOptions,
            array_map(function ($keyword) {
              return "<code>'" . $keyword . "'</code>";
            }, $keywordOptions)
          );
          $form[$policyTypeKey]['directives'][$directiveName]['options']['flags_wrapper']['flags'] = [
            '#type' => 'checkboxes',
            '#parents' => [$policyTypeKey, 'directives', $directiveName, 'flags'],
            '#options' => $keywordOptions,
            '#default_value' => $config->get($policyTypeKey . '.directives.' . $directiveName . '.flags') ?: [],
          ];
        }
        if (!empty($autoDirectives[$directiveName])) {
          $form[$policyTypeKey]['directives'][$directiveName]['options']['auto'] = [
            '#type' => 'textarea',
            '#parents' => [$policyTypeKey, 'directives', $directiveName, 'auto'],
            '#title' => 'Auto Sources',
            '#value' => implode(' ', $autoDirectives[$directiveName]),
            '#disabled' => TRUE,
          ];
        }
        $form[$policyTypeKey]['directives'][$directiveName]['options']['sources'] = [
          '#type' => 'textarea',
          '#parents' => [$policyTypeKey, 'directives', $directiveName, 'sources'],
          '#title' => $this->t('Additional Sources'),
          '#description' => $this->t('Additional domains or protocols to allow for this directive, separated by a space.'),
          '#default_value' => implode(' ', $config->get($policyTypeKey . '.directives.' . $directiveName . '.sources') ?: []),
          '#config_target' => new ConfigTarget(
            'csp.settings',
            $policyTypeKey . '.directives.' . $directiveName . '.sources',
            toConfig: fn() => ToConfig::NoOp,
          ),
          '#states' => [
            'visible' => [
              [':input[name="' . $policyTypeKey . '[directives][' . $directiveName . '][base]"]' => ['!value' => 'none']],
            ],
          ],
        ];
      }

      $form[$policyTypeKey]['directives']['child-src']['options']['note'] = [
        '#type' => 'markup',
        '#markup' => '<em>' . $this->t('Instead of child-src, nested browsing contexts and workers should use the frame-src and worker-src directives, respectively.') . '</em>',
        '#weight' => -10,
      ];

      if ($policyTypeKey === 'enforce') {
        // block-all-mixed content is a no-op if upgrade-insecure-requests is
        // enabled.
        // @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/block-all-mixed-content
        $form[$policyTypeKey]['directives']['block-all-mixed-content']['#states'] = [
          'disabled' => [
            [':input[name="' . $policyTypeKey . '[directives][upgrade-insecure-requests][enable]"]' => ['checked' => TRUE]],
          ],
        ];
      }

      // 'sandbox' token values are defined by HTML specification for the iframe
      // sandbox attribute.
      // @see https://www.w3.org/TR/CSP/#directive-sandbox
      // @see https://html.spec.whatwg.org/multipage/iframe-embed-object.html#attr-iframe-sandbox
      $form[$policyTypeKey]['directives']['sandbox']['options']['keys'] = [
        '#type' => 'checkboxes',
        '#parents' => [$policyTypeKey, 'directives', 'sandbox', 'keys'],
        '#options' => [
          'allow-downloads' => '<code>allow-downloads</code>',
          'allow-forms' => '<code>allow-forms</code>',
          'allow-modals' => '<code>allow-modals</code>',
          'allow-orientation-lock' => '<code>allow-orientation-lock</code>',
          'allow-pointer-lock' => '<code>allow-pointer-lock</code>',
          'allow-popups' => '<code>allow-popups</code>',
          'allow-popups-to-escape-sandbox' => '<code>allow-popups-to-escape-sandbox</code>',
          'allow-presentation' => '<code>allow-presentation</code>',
          'allow-same-origin' => '<code>allow-same-origin</code>',
          'allow-scripts' => '<code>allow-scripts</code>',
          'allow-top-navigation' => '<code>allow-top-navigation</code>',
          'allow-top-navigation-by-user-activation' => '<code>allow-top-navigation-by-user-activation</code>',
          'allow-top-navigation-to-custom-protocols' => '<code>allow-top-navigation-to-custom-protocols</code>',
        ],
        '#default_value' => $config->get($policyTypeKey . '.directives.sandbox') ?: [],
      ];

      $form[$policyTypeKey]['directives']['webrtc']['options']['value'] = [
        '#type' => 'radios',
        '#parents' => [$policyTypeKey, 'directives', 'webrtc', 'value'],
        '#options' => [
          'allow' => "<code>'allow'</code>",
          'block' => "<code>'block'</code>",
        ],
        '#default_value' => $config->get($policyTypeKey . '.directives.webrtc') ?? 'block',
      ];

      $form[$policyTypeKey]['directives']['trusted-types']['options']['base'] = [
        '#type' => 'radios',
        '#parents' => [$policyTypeKey, 'directives', 'trusted-types', 'base'],
        '#options' => [
          'none' => "None",
          'any' => "Any",
          '' => '<em>n/a</em>',
        ],
        '#default_value' => $config->get($policyTypeKey . '.directives.trusted-types.base') ?? '',
      ];

      $form[$policyTypeKey]['directives']['trusted-types']['options']['allow-duplicates'] = [
        '#type' => 'checkbox',
        '#parents' => [$policyTypeKey, 'directives', 'trusted-types', 'allow-duplicates'],
        '#title' => "<code>'allow-duplicates'</code>",
        '#default_value' => $config->get($policyTypeKey . '.directives.trusted-types.allow-duplicates') ?? FALSE,
        '#states' => [
          'visible' => [
            [':input[name="' . $policyTypeKey . '[directives][trusted-types][base]"]' => ['!value' => 'none']],
          ],
        ],
      ];
      $form[$policyTypeKey]['directives']['trusted-types']['options']['policy-names'] = [
        '#type' => 'textarea',
        '#parents' => [$policyTypeKey, 'directives', 'trusted-types', 'policy-names'],
        '#title' => $this->t('Policy Names'),
        '#default_value' => implode(' ', $config->get($policyTypeKey . '.directives.trusted-types.policy-names') ?? []),
        '#config_target' => new ConfigTarget(
          'csp.settings',
          $policyTypeKey . '.directives.trusted-types.policy-names',
          toConfig: fn() => ToConfig::NoOp,
        ),
        '#states' => [
          '!visible' => [
            [
              ':input[name="' . $policyTypeKey . '[directives][trusted-types][base]"]' => [
                ['value' => 'none'],
                'or',
                ['value' => 'any'],
              ],
            ],
          ],
        ],
      ];

      $form[$policyTypeKey]['directives']['require-trusted-types-for']['options']['sink-groups'] = [
        '#type' => 'checkboxes',
        '#parents' => [$policyTypeKey, 'directives', 'require-trusted-types-for', 'sink-groups'],
        '#options' => [
          'script' => "<code>'script'</code>",
        ],
        // 'script' is currently the only option, so is always included if
        // directive is enabled.
        // See https://w3c.github.io/trusted-types/dist/spec/#require-trusted-types-for-csp-directive.
        '#default_value' => ['script'],
        '#disabled' => TRUE,
      ];

      $form[$policyTypeKey]['reporting'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Reporting'),
        '#tree' => TRUE,
        '#config_target' => new ConfigTarget(
          'csp.settings',
          $policyTypeKey . '.reporting',
          toConfig: self::reportingToConfig(...),
        ),
      ];
      $form[$policyTypeKey]['reporting']['handler'] = [
        '#type' => 'radios',
        '#title' => $this->t('Handler'),
        '#options' => [],
        '#default_value' => $config->get($policyTypeKey . '.reporting.plugin') ?: 'none',
      ];

      // Warn before form validation if configuration contains unavailable
      // plugin.  Validation will set its own warning.
      if (empty($form_state->getUserInput()) && !array_key_exists($config->get($policyTypeKey . '.reporting.plugin'), $reportingHandlerPluginDefinitions)) {
        $this->messenger()->addError(
          $this->t("Configured %policy_type reporting plugin '%plugin_id' is not available.", [
            '%policy_type' => $policyTypeKey,
            '%plugin_id' => $config->get($policyTypeKey . '.reporting.plugin'),
          ])
        );
      }

      foreach ($reportingHandlerPluginDefinitions as $reportingHandlerPluginDefinition) {
        $reportingHandlerOptions = [
          'type' => $policyTypeKey,
        ];
        if ($config->get($policyTypeKey . '.reporting.plugin') == $reportingHandlerPluginDefinition['id']) {
          $reportingHandlerOptions += $config->get($policyTypeKey . '.reporting.options') ?: [];
        }

        try {
          $reportingHandlerPlugin = $this->reportingHandlerPluginManager->createInstance(
            $reportingHandlerPluginDefinition['id'],
            $reportingHandlerOptions
          );
        }
        catch (PluginException) {
          \Drupal::logger('csp')
            ->error("Could not load '%plugin_id' reporting plugin", ['%plugin_id' => $reportingHandlerPluginDefinition['id']]);
          continue;
        }

        $form[$policyTypeKey]['reporting']['handler']['#options'][$reportingHandlerPluginDefinition['id']] = $reportingHandlerPluginDefinition['label'];

        $form[$policyTypeKey]['reporting'][$reportingHandlerPluginDefinition['id']] = $reportingHandlerPlugin->getForm([
          '#type' => 'item',
          '#description' => $reportingHandlerPluginDefinition['description'],
          '#states' => [
            'visible' => [
              ':input[name="' . $policyTypeKey . '[reporting][handler]"]' => ['value' => $reportingHandlerPluginDefinition['id']],
            ],
          ],
        ]);
      }

      $form[$policyTypeKey]['clear'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset @policyType policy to default values', ['@policyType' => $policyTypeName]),
        '#cspPolicyType' => $policyTypeKey,
        '#button_type' => 'danger',
        '#submit' => [
          '::submitClearPolicy',
        ],
      ];
    }

    // Skip this check when building the form before validation/submission.
    if (empty($form_state->getUserInput())) {
      $enabledPolicies = array_filter(array_keys($policyTypes), function ($policyTypeKey) use ($config) {
        return $config->get($policyTypeKey . '.enable');
      });
      if (empty($enabledPolicies)) {
        $this->messenger()
          ->addWarning($this->t('No policies are currently enabled.'));
      }

      foreach ($policyTypes as $policyTypeKey => $policyTypeName) {
        if (!$config->get($policyTypeKey . '.enable')) {
          continue;
        }

        foreach ($directiveNames as $directive) {
          if (
            !str_starts_with($directive, 'script-src')
            && !str_starts_with($directive, 'style-src')
          ) {
            continue;
          }
          if (($directiveSources = $config->get($policyTypeKey . '.directives.' . $directive . '.sources'))) {

            // '{hashAlgorithm}-{base64-value}'
            $hashAlgoMatch = '(' . implode('|', Csp::HASH_ALGORITHMS) . ')-[\w+/_-]+=*';
            $hasHashSource = array_reduce(
              $directiveSources,
              function ($return, $value) use ($hashAlgoMatch) {
                return $return || preg_match("<^'" . $hashAlgoMatch . "'$>", $value);
              },
              FALSE
            );
            if ($hasHashSource) {
              $this->messenger()->addWarning($this->t(
                '%policy %directive has a hash source configured, which may block functionality that relies on inline code.',
                [
                  '%policy' => $policyTypeName,
                  '%directive' => $directive,
                ]
              ));
            }
          }
        }

        foreach (['script-src', 'style-src'] as $directive) {
          foreach (['-attr', '-elem'] as $subdirective) {
            if ($config->get($policyTypeKey . '.directives.' . $directive . $subdirective)) {
              foreach (Csp::getDirectiveFallbackList($directive . $subdirective) as $fallbackDirective) {
                if ($config->get($policyTypeKey . '.directives.' . $fallbackDirective)) {
                  continue 2;
                }
              }
              $this->messenger()->addWarning($this->t(
                '%policy %directive is enabled without a fallback directive for non-supporting browsers.',
                [
                  '%policy' => $policyTypeName,
                  '%directive' => $directive . $subdirective,
                ]
              ));
            }
          }
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach (['report-only', 'enforce'] as $policyTypeKey) {
      if (($reportingHandlerPluginId = $form_state->getValue([$policyTypeKey, 'reporting', 'handler']))) {
        if ($this->reportingHandlerPluginManager->hasDefinition($reportingHandlerPluginId)) {
          $this->reportingHandlerPluginManager->createInstance($reportingHandlerPluginId, ['type' => $policyTypeKey])
            ->validateForm($form[$policyTypeKey]['reporting'][$reportingHandlerPluginId], $form_state);
        }
        // Radio element validation will add error if invalid plugin provided.
      }
      else {
        $form_state->setError(
          $form[$policyTypeKey]['reporting']['handler'],
          $this->t('Reporting Handler is required for enabled policies.')
        );
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $form_element_name
   *   The form element for which to format multiple violation messages.
   * @param array<ConstraintViolationInterface> $violations
   *   The list of constraint violations that apply to this form element.
   */
  protected function formatMultipleViolationsMessage(string $form_element_name, array $violations): TranslatableMarkup {
    /** @var array{("report-only"|"enforce"), string, string, string} $elementKeys */
    $elementKeys = explode('][', $form_element_name);

    $messageArgs = fn () => [
      '%policy' => match($elementKeys[0]) {
        'report-only' => $this->t('Report Only'),
        'enforce' => $this->t('Enforced'),
      },
      '%directive' => $elementKeys[2],
      '%value' => implode(', ', array_map(
        function (ConstraintViolationInterface $violation): string {
          return $violation->getInvalidValue();
        },
        $violations
      )),
    ];

    if (str_ends_with($form_element_name, 'sources')) {
      return $this->formatPlural(
        count($violations),
        'Invalid %policy %directive source: %value',
        'Invalid %policy %directive sources: %value',
        $messageArgs()
      );
    }
    elseif (str_ends_with($form_element_name, 'trusted-types][policy-names')) {
      return $this->formatPlural(
        count($violations),
        'Invalid %policy Trusted Types Policy Name: %value',
        'Invalid %policy Trusted Types Policy Names: %value',
        $messageArgs()
      );
    }

    // Drupal ^11.0.6 returns MarkupInterface|\Stringable, which need conversion
    // on prior versions to respect TranslatableMarkup type hint.
    $parent = parent::formatMultipleViolationsMessage($form_element_name, $violations);
    // @phpstan-ignore-next-line
    if ($parent instanceof TranslatableMarkup) {
      return $parent;
    }
    else {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      return $this->t((string) $parent);
    }
  }

  /**
   * Submit handler for clear policy buttons.
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitClearPolicy(array &$form, FormStateInterface $form_state): void {
    $submitElement = $form_state->getTriggeringElement();

    $this->config('csp.settings')
      ->clear($submitElement['#cspPolicyType'])
      ->save();
  }

  /**
   * Convert form state to config array for directive options.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string, mixed>|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function directiveToConfig(array $value): array|ToConfig {
    if (!$value['enable']) {
      return ToConfig::DeleteKey;
    }
    unset($value['enable']);

    return $value;
  }

  /**
   * Convert form state to config value for a boolean directive.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return bool|\Drupal\Core\Form\ToConfig
   *   Directive config value, or a ToConfig enum value.
   */
  private static function booleanToConfig(array $value): bool|ToConfig {
    return !empty($value['enable']) ? TRUE : ToConfig::DeleteKey;
  }

  /**
   * Convert form state to config value for a token list directive.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string>|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function tokenListToConfig(array $value): array|ToConfig {
    if (!$value['enable']) {
      return ToConfig::DeleteKey;
    }
    unset($value['enable']);

    return array_keys(array_filter($value['keys']));
  }

  /**
   * Convert form state to config value for an allow/block directive.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return string|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function allowBlockToConfig(array $value): string|ToConfig {
    return !empty($value['enable']) ? $value['value'] : ToConfig::DeleteKey;
  }

  /**
   * Convert form state to config array for source list directive.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string, mixed>|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function sourceListToConfig(array $value): array|ToConfig {
    if (!$value['enable']) {
      return ToConfig::DeleteKey;
    }
    unset($value['enable'], $value['auto']);

    if ($value['base'] === 'none') {
      return ['base' => 'none'];
    }

    $value['sources'] = array_filter(preg_split('/,?\s+/', $value['sources']));

    if (array_key_exists('flags', $value)) {
      $value['flags'] = array_keys(array_filter($value['flags']));
    }

    return array_filter($value);
  }

  /**
   * Convert form state to config array for trusted types directive.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string, mixed>|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function trustedTypesToConfig(array $value): array|ToConfig {
    if (!$value['enable']) {
      return ToConfig::DeleteKey;
    }
    unset($value['enable']);

    $value['allow-duplicates'] = $value['base'] !== 'none' && $value['allow-duplicates'];
    $value['policy-names'] = $value['base'] === '' ?
      array_filter(preg_split('/,?\s+/', $value['policy-names'])) :
      [];

    return $value;
  }

  /**
   * Convert form state to config array for trusted types sink groups.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string>|\Drupal\Core\Form\ToConfig
   *   Directive config array, or a ToConfig enum value.
   */
  private static function sinkGroupsToConfig(array $value): array|ToConfig {
    // Script is currently the only valid value, so set it when enabled.
    // See https://w3c.github.io/trusted-types/dist/spec/#require-trusted-types-for-csp-directive.
    return !empty($value['enable']) ? ['script'] : ToConfig::DeleteKey;
  }

  /**
   * Convert form state to config array for reporting options.
   *
   * @param array<string, mixed> $value
   *   The submitted form values.
   *
   * @return array<string, mixed>
   *   Reporting config array.
   */
  private static function reportingToConfig(array $value): array {
    $return = [
      'plugin' => $value['handler'],
    ];
    if (!empty($value[$value['handler']])) {
      $return['options'] = $value[$value['handler']];
    }

    return $return;
  }

}
