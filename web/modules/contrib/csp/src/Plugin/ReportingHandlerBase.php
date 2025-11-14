<?php

namespace Drupal\csp\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\csp\Csp;

/**
 * Base Reporting Handler implementation.
 */
abstract class ReportingHandlerBase implements ReportingHandlerInterface {

  /**
   * The Plugin Configuration.
   *
   * @var array<string, mixed>
   */
  protected array $configuration;

  /**
   * The Plugin ID.
   *
   * @var string
   */
  protected string $pluginId;

  /**
   * The Plugin Definition.
   *
   * @var array
   */
  protected array $pluginDefinition;

  /**
   * Reporting Handler plugin constructor.
   *
   * @param array<string, mixed> $configuration
   *   The Plugin configuration.
   * @param string $plugin_id
   *   The Plugin ID.
   * @param array $plugin_definition
   *   The Plugin Definition.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function alterPolicy(Csp $policy): void {

  }

}
