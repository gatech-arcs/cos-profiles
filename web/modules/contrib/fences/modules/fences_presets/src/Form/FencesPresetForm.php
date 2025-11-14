<?php

declare(strict_types=1);

namespace Drupal\fences_presets\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fences\TagManager;
use Drupal\fences\TagManagerInterface;
use Drupal\fences_presets\Entity\FencesPreset;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fences Preset form.
 */
final class FencesPresetForm extends EntityForm implements ContainerInjectionInterface {

  /**
   * The tag manager.
   *
   * @var \Drupal\fences\TagManager
   */
  protected $tagManager;

  /**
   * The form constructor.
   *
   * @param \Drupal\fences\TagManager $tagManager
   *   The tag manager.
   */
  public function __construct(TagManager $tagManager) {
    $this->tagManager = $tagManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the services required to construct this class.
      $container->get('fences.tag_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {

    $form = parent::form($form, $form_state);
    $tag_options = $this->tagManager->getTagOptions();

    $invisible_on_none = function ($name) {
      return [
        'invisible' => [
          ':input[name="' . $name . '"]' => [
            'value' => TagManagerInterface::NO_MARKUP_VALUE,
          ],
        ],
      ];
    };

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [FencesPreset::class, 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->get('description'),
    ];

    $form['values'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preset values'),
    ];

    $form['values']['field_tag'] = [
      '#title' => $this->t('Field Tag'),
      '#type' => 'select',
      '#options' => $tag_options,
      '#default_value' => $this->entity->get('field_tag'),
    ];

    $form['values']['field_classes'] = [
      '#title' => $this->t('Field Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('field_classes'),
      '#states' => $invisible_on_none('field_tag'),
      '#description' => $this->t('Enter additional classes, separated by space.'),
    ];

    $form['values']['field_items_wrapper_tag'] = [
      '#title' => $this->t('Field Items Wrapper Tag'),
      '#type' => 'select',
      '#options' => $tag_options,
      '#default_value' => $this->entity->get('field_items_wrapper_tag'),
    ];

    $form['values']['field_items_wrapper_classes'] = [
      '#title' => $this->t('Field Items Wrapper Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('field_items_wrapper_classes'),
      '#states' => $invisible_on_none('field_items_wrapper_tag'),
      '#description' => $this->t('Enter additional classes, separated by space.'),
    ];

    $form['values']['field_item_tag'] = [
      '#title' => $this->t('Field Item Tag'),
      '#type' => 'select',
      '#options' => $tag_options,
      '#default_value' => $this->entity->get('field_item_tag'),
    ];

    $form['values']['field_item_classes'] = [
      '#title' => $this->t('Field Item Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('field_item_classes'),
      '#states' => $invisible_on_none('field_item_tag'),
      '#description' => $this->t('Enter additional classes, separated by space.'),
    ];

    $form['values']['label_tag'] = [
      '#title' => $this->t('Label Tag'),
      '#type' => 'select',
      '#options' => $tag_options,
      '#default_value' => $this->entity->get('label_tag'),
    ];

    $form['values']['label_classes'] = [
      '#title' => $this->t('Label Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('label_classes'),
      '#states' => $invisible_on_none('label_tag'),
      '#description' => $this->t('Enter additional classes, separated by space.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        \SAVED_NEW => $this->t('Created new %label.', $message_args),
        \SAVED_UPDATED => $this->t('Updated %label.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
