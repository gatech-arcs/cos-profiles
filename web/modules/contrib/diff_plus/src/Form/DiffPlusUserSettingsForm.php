<?php

namespace Drupal\diff_plus\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that allows individual users to set personalized settings.
 */
class DiffPlusUserSettingsForm extends DiffPlusSettingsFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->userData = $container->get('user.data');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_plus_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultConfiguration() {
    return array_replace(
      parent::defaultConfiguration(),
      $this->configFactory->get('diff_plus.settings')->get(),
      $this->userData->get('diff_plus', $this->currentUser->id(), 'settings') ?? []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form[] = [
      '#type' => 'item',
      '#markup' => $this->t('Update your personal Diff Plus settings.'),
    ];

    $form = parent::buildForm($form, $form_state);
    $form['submit']['#value'] = $this->t('Save my personal settings');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->defaultConfiguration();
    $overrides = array_intersect_key($form_state->getValues(), $settings);
    $data = array_replace(
      $this->defaultConfiguration(),
      $overrides
    );
    $this->userData->set('diff_plus', $this->currentUser->id(), 'settings', $data);
    $this->messenger->addStatus('Your personalized diff settings have been saved.');
  }

}
