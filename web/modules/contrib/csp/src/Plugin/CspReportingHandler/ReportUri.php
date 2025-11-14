<?php

namespace Drupal\csp\Plugin\CspReportingHandler;

use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\ToConfig;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\csp\Csp;
use Drupal\csp\Plugin\ReportingHandlerBase;

/**
 * CSP Reporting Plugin for ReportURI service.
 *
 * @CspReportingHandler(
 *   id = "report-uri-com",
 *   label = "Report URI",
 *   description = @Translation("Reports will be sent to a ReportURI.com account."),
 * )
 *
 * @see report-uri.com
 */
class ReportUri extends ReportingHandlerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form): array {

    $form['subdomain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subdomain'),
      '#description' => $this->t('Your <a href=":url">Report-URI.com subdomain</a>.', [
        ':url' => 'https://report-uri.com/account/setup/',
      ]),
      '#default_value' => $this->configuration['subdomain'] ?? '',
      '#config_target' => new ConfigTarget(
        'csp.settings',
        $this->configuration['type'] . '.reporting.options.subdomain',
        toConfig: fn() => ToConfig::NoOp,
      ),
      '#states' => [
        'required' => [
          ':input[name="' . $this->configuration['type'] . '[enable]"]' => ['checked' => TRUE],
          ':input[name="' . $this->configuration['type'] . '[reporting][handler]"]' => ['value' => $this->pluginId],
        ],
      ],
    ];

    if ($this->configuration['type'] == 'report-only') {
      $form['wizard'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Wizard'),
        '#description' => $this->t('Send reports to the <a href=":url">CSP Wizard</a> reporting address.', [
          ':url' => 'https://report-uri.com/account/wizard/csp/',
        ]),
        '#default_value' => !empty($this->configuration['wizard']),
      ];
    }

    unset($form['#description']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterPolicy(Csp $policy): void {
    $type = 'enforce';

    if ($this->configuration['type'] == 'report-only') {
      $type = empty($this->configuration['wizard']) ? 'reportOnly' : 'wizard';
    }

    $policy->setDirective(
      'report-uri',
      'https://' . $this->configuration['subdomain'] . '.report-uri.com/r/d/csp/' . $type
    );
  }

}
