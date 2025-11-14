<?php

namespace Drupal\lb_direct_add\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'lb_direct_add.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lb_direct_add_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $form['use_label'] = [
      '#type' => 'radios',
      '#title' => $this->t('How to display list'),
      '#options' => [
        0 => $this->t('Dropbutton'),
        1 => $this->t('Popover menu'),
      ],
      '#default_value' => $config->get('use_label') ?: 0,
      '#description' => $this->t('The dropbutton option uses the built-in Drupal form element that is commonly used throughout the UI. The popover menu option provides a clickable trigger which shows the menu.'),
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label name'),
      '#description' => $this->t('Enter a label that the user will click to display the list.'),
      '#default_value' => $config->get('label') ?: 'Add block',
      '#states' => [
        'visible' => [
          ':input[name="use_label"]' => [
            'value' => 1,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config(static::SETTINGS);
    foreach ($form_state->cleanValues()->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

}
