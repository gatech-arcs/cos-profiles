<?php

namespace Drupal\diff_plus\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form that allows site builders to set default settings.
 */
class DiffPlusDefaultSettingsForm extends DiffPlusSettingsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_plus_default_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultConfiguration() {
    return array_replace(
      parent::defaultConfiguration(),
      $this->configFactory->get('diff_plus.settings')->get()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[] = [
      '#type' => 'item',
      '#markup' => $this->t('Update the default Diff Plus settings for all users.'),
    ];
    $form = parent::buildForm($form, $form_state);
    $form['submit']['#value'] = $this->t('Save default settings');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $defaults = $this->defaultConfiguration();
    $overrides = array_intersect_key($form_state->getValues(), $defaults);
    $data = array_replace(
      $this->defaultConfiguration(),
      $overrides
    );
    $this->configFactory->getEditable('diff_plus.settings')->setData($data)->save();

    $this->messenger->addStatus('Default diff settings have been saved.');
  }

}
