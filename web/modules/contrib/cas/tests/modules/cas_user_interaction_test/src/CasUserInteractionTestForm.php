<?php

declare(strict_types=1);

namespace Drupal\cas_user_interaction_test;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\cas\Service\CasUserManager;

/**
 * Form that enforces the user to accept the new site's 'Legal Notice'.
 */
class CasUserInteractionTestForm extends FormBase {

  use AutowireTrait;

  public function __construct(
    protected readonly CasUserManager $casUserManager,
    protected readonly PrivateTempStoreFactory $privateTempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['accept'] = [
      '#type' => 'checkbox',
      '#title' => "I agree with the 'Legal Notice'",
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'I agree',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $tempstore = $this->privateTempStoreFactory->get('cas_user_interaction_test');
    $this->casUserManager->login($tempstore->get('property_bag'), $tempstore->get('ticket'));
    $form_state->setRedirectUrl(Url::fromUserInput('/'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cas_user_interaction_test_form';
  }

}
