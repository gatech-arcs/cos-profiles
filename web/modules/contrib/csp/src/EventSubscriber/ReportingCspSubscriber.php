<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\ReportingHandlerPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP Policies with reporting plugin.
 */
class ReportingCspSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter', 0];
    return $events;
  }

  /**
   * Construct a CSP policy event subscriber for reporting handlers.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ReportingHandlerPluginManager $reportingHandlerPluginManager,
  ) {

  }

  /**
   * Apply configured reporting handler settings to a policy.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The policy alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $config = $this->configFactory->get('csp.settings');

    $response = $alterEvent->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheableDependency($config);
    }

    $policy = $alterEvent->getPolicy();
    $policyType = $policy->isReportOnly() ? 'report-only' : 'enforce';

    $reportingPluginId = $config->get($policyType . '.reporting.plugin');
    if ($reportingPluginId) {
      $reportingOptions = $config->get($policyType . '.reporting.options') ?: [];
      $reportingOptions += [
        'type' => $policyType,
      ];
      try {
        $this->reportingHandlerPluginManager
          ->createInstance($reportingPluginId, $reportingOptions)
          ->alterPolicy($policy);
      }
      catch (PluginException $e) {
        \Drupal::logger('csp')
          ->error(Error::DEFAULT_ERROR_MESSAGE, Error::decodeException($e));
      }
    }
  }

}
