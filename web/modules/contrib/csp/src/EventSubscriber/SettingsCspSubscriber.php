<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policies based on module configuration.
 */
class SettingsCspSubscriber implements EventSubscriberInterface {

  /**
   * The CSP module settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  private readonly Config $config;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter', 256];
    return $events;
  }

  /**
   * Construct a CSP policy event subscriber for module settings.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get('csp.settings');
  }

  /**
   * Apply the module settings for directives to a policy.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The policy alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $response = $alterEvent->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheableDependency($this->config);
    }

    $policy = $alterEvent->getPolicy();
    $policyType = $policy->isReportOnly() ? 'report-only' : 'enforce';

    if (!$this->config->get($policyType . '.enable')) {
      return;
    }

    foreach (($this->config->get($policyType . '.directives') ?: []) as $directiveName => $directiveOptions) {
      assert(array_key_exists($directiveName, Csp::DIRECTIVES), 'Settings contained configuration for unknown directive "' . $directiveName . '"');

      if (Csp::DIRECTIVES[$directiveName] == Csp::DIRECTIVE_SCHEMA_BOOLEAN) {
        $policy->setDirective($directiveName, (bool) $directiveOptions);
        continue;
      }

      if (Csp::DIRECTIVES[$directiveName] === Csp::DIRECTIVE_SCHEMA_ALLOW_BLOCK) {
        if (!empty($directiveOptions)) {
          $policy->setDirective($directiveName, "'" . $directiveOptions . "'");
        }
        continue;
      }

      // This is a directive with a simple array of values.
      if (in_array(Csp::DIRECTIVES[$directiveName], [
        Csp::DIRECTIVE_SCHEMA_MEDIA_TYPE_LIST,
        Csp::DIRECTIVE_SCHEMA_OPTIONAL_TOKEN_LIST,
        Csp::DIRECTIVE_SCHEMA_TOKEN,
        Csp::DIRECTIVE_SCHEMA_TOKEN_LIST,
        Csp::DIRECTIVE_SCHEMA_URI_REFERENCE_LIST,
        Csp::DIRECTIVE_SCHEMA_TRUSTED_TYPES_SINK_GROUPS,
      ])) {
        $policy->appendDirective($directiveName, $directiveOptions);
        continue;
      }

      if (Csp::DIRECTIVES[$directiveName] === Csp::DIRECTIVE_SCHEMA_TRUSTED_TYPES) {
        $trusted_types = [];
        if (!empty($directiveOptions['base'])) {
          $trusted_types[] = match($directiveOptions['base']) {
            'any' => '*',
            'none' => "'none'",
            default => throw new \UnhandledMatchError("Unknown Trusted Types base value"),
          };
        }
        if ($directiveOptions['base'] !== 'none' && $directiveOptions['allow-duplicates']) {
          $trusted_types[] = "'allow-duplicates'";
        }
        if ($directiveOptions['base'] === '') {
          $trusted_types = array_merge($trusted_types, $directiveOptions['policy-names']);
        }
        $policy->appendDirective($directiveName, $trusted_types);
        continue;
      }

      // Source List or Ancestor Source List.
      if (in_array(Csp::DIRECTIVES[$directiveName], [
        Csp::DIRECTIVE_SCHEMA_SOURCE_LIST,
        Csp::DIRECTIVE_SCHEMA_ANCESTOR_SOURCE_LIST,
      ])) {
        $policy->appendDirective(
          $directiveName,
          match($directiveOptions['base'] ?? NULL) {
            'self' => [Csp::POLICY_SELF],
            'none' => [Csp::POLICY_NONE],
            'any'  => [Csp::POLICY_ANY],
            // Initialize to an empty value so that any alter subscribers can
            // tell that this directive was enabled.
            default => [],
          }
        );
        if (!empty($directiveOptions['flags'])) {
          $policy->appendDirective($directiveName, array_map(function ($value) {
            return "'" . $value . "'";
          }, $directiveOptions['flags']));
        }

        if (!empty($directiveOptions['sources'])) {
          $policy->appendDirective($directiveName, $directiveOptions['sources']);
        }
      }
    }
  }

}
