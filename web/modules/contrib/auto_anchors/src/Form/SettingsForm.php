<?php

namespace Drupal\auto_anchors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a settings form for this module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'auto_anchors.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_anchors_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auto_anchors.settings');

    $form['root_elements'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root elements'),
      '#description' => $this->t("A comma-separated list of valid CSS selectors. Automatic ids will be generated on matched elements within these root elements."),
      '#default_value' => $config->get('root_elements'),
      '#required' => TRUE,
    ];
    $form['anchor_elements'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anchor elements'),
      '#description' => $this->t("A comma-separated list of valid CSS selectors. Automatic ids will be generated on these matched elements within the root element(s) specified above."),
      '#default_value' => $config->get('anchor_elements'),
      '#required' => TRUE,
    ];
    $form['link_content'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link content'),
      '#description' => $this->t("The text/HTML to use when displaying permalinks to automatic anchors. This is the text that will appear between the inserted @a tags.", ['@a' => '<a>...</a>']),
      '#default_value' => $config->get('link_content'),
      '#required' => TRUE,
    ];
    $form['exclude_admin_pages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages'),
      '#description' => $this->t('If checked, automatic ids and permalinks will not be generated on administrative pages.'),
      '#default_value' => $config->get('exclude_admin_pages'),
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('auto_anchors.settings')
      ->set('root_elements', $form_state->getValue('root_elements'))
      ->set('anchor_elements', $form_state->getValue('anchor_elements'))
      ->set('link_content', $form_state->getValue('link_content'))
      ->set('exclude_admin_pages', $form_state->getValue('exclude_admin_pages'))
      ->save();
  }

}
