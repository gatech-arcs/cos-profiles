<?php

/**
 * @file
 * Contains Drupal\ckeditor_responsive_table\Form\CkeditorResponsiveTableForm.
 */

namespace Drupal\ckeditor_responsive_table\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CkeditorResponsiveTableForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_responsive_table_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('ckeditor_responsive_table.settings');
    $default_table_selector = $config->get('table_selector');
    if (empty($default_table_selector)) {
      $default_table_selector = $config->get('default_table_selector');
    }
    $default_fail_class = $config->get('fail_class');
    if (empty($default_fail_class)) {
      $default_fail_class = $config->get('default_fail_class');
    }
    $default_caption_side = $config->get('caption_side');
    if (empty($default_caption_side)) {
      $default_caption_side = $config->get('default_caption_side');
    }
    $default_large_character_threshold = $config->get('large_character_threshold');
    if (empty($default_large_character_threshold)) {
      $default_large_character_threshold = $config->get('defaul_large_character_threshold');
    }
    $default_small_character_threshold = $config->get('small_character_threshold');
    if (empty($default_small_character_threshold)) {
      $default_small_character_threshold = $config->get('default_small_character_threshold');
    }

    // Table selector field.
    $form['table_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table selector'),
      '#default_value' => $default_table_selector,
      '#description' => $this->t('Enter a CSS selector to apply the responsive table script to.'),
    ];

    // Fail class field.
    $form['fail_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fail class'),
      '#default_value' => $default_fail_class,
      '#description' => $this->t("The CSS class to be added to the table if it doesn't meet the requirements."),
    ];

    // Caption side field.
    $form['caption_side'] = [
      '#type' => 'radios',
      '#title' => $this->t('Caption side'),
      '#default_value' => $default_caption_side,
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#description' => $this->t('The side where the table caption should be placed.'),
    ];

    // Large character threshold field.
    $form['large_character_threshold'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#title' => $this->t('Large character theshold'),
      '#default_value' => $default_large_character_threshold,
      '#description' => $this->t('The character threshold for determining if a cell should have a large width.'),
    ];

    // Small character threshold field.
    $form['small_character_threshold'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#title' => $this->t('Small character theshold'),
      '#default_value' => $default_small_character_threshold,
      '#description' => $this->t('The character threshold for determining if a cell should have a small width.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_responsive_table.settings');
    $config->set('table_selector', $form_state->getValue('table_selector'));
    $config->set('fail_class', $form_state->getValue('fail_class'));
    $config->set('caption_side', $form_state->getValue('caption_side'));
    $config->set('large_character_threshold', $form_state->getValue('large_character_threshold'));
    $config->set('small_character_threshold', $form_state->getValue('small_character_threshold'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckeditor_responsive_table.settings',
    ];
  }

}
