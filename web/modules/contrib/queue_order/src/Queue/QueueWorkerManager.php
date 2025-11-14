<?php

namespace Drupal\queue_order\Queue;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

/**
 * Class QueueWorkerManager.
 *
 * Plugin manager extension class.
 *
 * @package Drupal\queue_order\Queue
 */
class QueueWorkerManager implements QueueWorkerManagerInterface, CachedDiscoveryInterface {

  /**
   * Constructs an QueueWorkerManager object.
   *
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $subject
   *   Decorated plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The module configs.
   */
  public function __construct(
    protected QueueWorkerManagerInterface $subject,
    protected ConfigFactoryInterface $config,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return self::sortDefinitions(
      $this->subject->getDefinitions(),
      $this->config->get('queue_order.settings')->get('order') ?: []
    );
  }

  /**
   * Reorder Queue worker definitions.
   *
   * @param array $definitions
   *   Queue worker definitions.
   * @param array $weights
   *   Weight overrides.
   *
   * @return array
   *   Reordered Queue worker definitions.
   */
  protected static function sortDefinitions(array $definitions, array $weights): array {
    // Prepare definitions for sorting.
    foreach ($definitions as $key => $definition) {
      // Define default weight value or hint defined weight to the int value.
      // And check weight value overrides.
      $definitions[$key]['weight'] = (int) ($weights[$key] ?? ($definition['weight'] ?? 0));
    }
    // Sort definitions by weight element.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->subject->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->subject->hasDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return $this->subject->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->subject->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    return $this->subject->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    return $this->subject->clearCachedDefinitions();
  }

}
