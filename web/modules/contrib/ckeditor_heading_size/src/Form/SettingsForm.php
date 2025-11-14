<?php

namespace Drupal\ckeditor_heading_size\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure CKEditor Heading Size settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_heading_size_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ckeditor_heading_size.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_heading_size.settings');
    $form['#attributes']['id'] = 'ckeditor-heading-size-settings-wrapper';
    $type = $form_state->getValue('type') ?? $config->get('type') ?? 'sizes';

    // Clear the text area when the radio is toggled.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#name']) && $trigger['#name'] === 'type') {
      $input = $form_state->getUserInput();
      $input['size_options'] = '';
      $form_state->setUserInput($input);
    }

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Font size type'),
      '#options' => [
        'sizes' => $this->t('Size options'),
        'classes' => $this->t('Theme classes'),
      ],
      '#default_value' => $type,
      '#ajax' => [
        'callback' => '::type',
        'wrapper' => 'ckeditor-heading-size-settings-wrapper',
        'effect' => 'fade',
      ],
    ];
    $form['size_options'] = [
      '#type' => 'textarea',
      '#title' => $type === 'sizes' ? $this->t('Heading Size Options') : $this->t('Theme classes'),
      '#description' => $type === 'sizes' ? $this->t("A list of font sizes that will be available as heading sizes. Enter one font size per line e.g. <br>16px<br>24px<br>48px") : $this->t('A list of key value pairs where the key is the label shown to the user and the value is the class from your theme to be applied to the heading. e.g. <br>My H1 style|my-h1-style<br>My H2 Style|my-h2-style'),
      '#default_value' =>  implode("\r\n", $config->get('size_options')) ?? '',
    ];
    $form['important'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Important'),
      '#default_value' => $config->get('important'),
      '#description' => $this->t('Flag the css rule as <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/important">!important</a>.')
    ];
    $form['extra_specificity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra specificity'),
      '#default_value' => $config->get('extra_specificity') ?? '',
      '#description' => $this->t('Provide some extra specificity to the heading tag css selector. Normally the css class looks something like h2.heading-size-14px. That may not be enough to override the theme\'s rule. You could add something like .main-container here and the output would be .main-container h2.heading-size-14px .'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback.
   */
  public function type(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ckeditor_heading_size.settings')
      ->set('type', $form_state->getValue('type'))
      ->set('important', $form_state->getValue('important'))
      ->set('extra_specificity', $form_state->getValue('extra_specificity'))
      ->set('size_options', explode("\r\n", $form_state->getValue('size_options')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
