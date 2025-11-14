<?php

namespace Drupal\csp\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\csp\Csp;

/**
 * CSP Reporting Handler interface.
 */
interface ReportingHandlerInterface {

  /**
   * Get the form fields for configuring this reporting handler.
   *
   * @param array<string, mixed> $form
   *   The plugin parent form element.
   *
   * @return array<string, mixed>
   *   A Form array.
   */
  public function getForm(array $form): array;

  /**
   * Validate the form fields of this report handler.
   *
   * @param array<string, mixed> $form
   *   The form fields for this plugin.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   *
   * @return void
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void;

  /**
   * Alter the provided policy according to the plugin settings.
   *
   * @param \Drupal\csp\Csp $policy
   *   The policy to alter.
   *
   * @return void
   */
  public function alterPolicy(Csp $policy): void;

}
