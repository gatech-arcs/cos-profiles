<?php

declare(strict_types=1);

namespace Drupal\cas_attributes\Form;

use Drupal\cas_attributes\Model\SyncFrequency;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\user\RoleInterface;
use Drupal\Core\Url;

/**
 * CAS Attributes settings form.
 */
class CasAttributesSettings extends ConfigFormBase {

  use AutowireTrait;

  /**
   * Sync frequency: never.
   *
   * @deprecated in cas_attributes:3.0.0 and is removed from
   *   cas_attributes:3.1.0. Instead, use SyncFrequency::Never.
   *
   * @see https://www.drupal.org/node/3494648
   * @see \Drupal\cas_attributes\Model\SyncFrequency
   */
  const SYNC_FREQUENCY_NEVER = 0;

  /**
   * Sync frequency: initial registration.
   *
   * @deprecated in cas_attributes:3.0.0 and is removed from
   *   cas_attributes:3.1.0. Instead, use SyncFrequency::Never.
   *
   * @see https://www.drupal.org/node/3494648
   * @see \Drupal\cas_attributes\Model\InitialRegistration
   */
  const SYNC_FREQUENCY_INITIAL_REGISTRATION = 1;

  /**
   * Sync frequency: every login.
   *
   * @deprecated in cas_attributes:3.0.0 and is removed from
   *   cas_attributes:3.1.0. Instead, use SyncFrequency::EveryLogin.
   *
   * @see https://www.drupal.org/node/3494648
   * @see \Drupal\cas_attributes\Model\SyncFrequency
   */
  const SYNC_FREQUENCY_EVERY_LOGIN = 2;

  /**
   * Roles structured as select options.
   */
  protected array $roleOptions;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cas_attributes_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas_attributes.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
      '#tree' => TRUE,
    ];
    $form['general']['sitewide_token_support'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sitewide token support'),
      '#description' => $this->t('When enabled, CAS attributes for the logged in user can be retrieved anywhere on your site using this token format: [cas:attribute:?], where ? is the attribute name in lowercase. <b>This is not required to use tokens for the user field mappings below.</b>'),
      '#config_target' => 'cas_attributes.settings:sitewide_token_support',
    ];

    $form['general']['token_allowed_attributes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Attributes'),
      '#description' => $this->t('CAS attributes to create tokens for, one per line. If no attributes are specified, then they will all be available as tokens. Case does not matter.'),
      '#config_target' => new ConfigTarget(
        configName: 'cas_attributes.settings',
        propertyPath: 'token_allowed_attributes',
        fromConfig: fn(array $value): string => implode("\n", $value),
        toConfig: fn(string $value): array => array_map(strtolower(...), array_filter(array_map(trim(...), explode("\n", str_replace("\r", "\n", $value))))),
      ),
      '#states' => [
        'visible' => [
          ':input[name="general[sitewide_token_support]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['field'] = [
      '#type' => 'details',
      '#title' => $this->t('User Field Mappings'),
      '#description' => $this->t('Configure settings for mapping CAS attribute values to user fields during login/registration.'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['field']['sync_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('When should field mappings be applied to the user?'),
      '#options' => SyncFrequency::asOptions(),
      '#config_target' => 'cas_attributes.settings:field.sync_frequency',
    ];

    $form['field']['overwrite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overwrite existing field values'),
      '#description' => $this->t('When checked, the field mappings below will always overwrite existing data on the user account.'),
      '#config_target' => 'cas_attributes.settings:field.overwrite',
    ];

    $form['field']['mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Fields'),
      '#description' => $this->t(
        'Optionally provide values for each user field below. To use a CAS attribute, insert a token in the format [cas:attribute:?], where ? is the attribute name in lowercase. <a href="@link">Browse available attribute tokens</a> for the currently logged in user. Note that attribute tokens will still work even if you have the "Sitewide token support" feature disabled (above).',
        ['@link' => Url::fromRoute('cas_attributes.available_attributes')->toString()]
      ),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['field']['mappings']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The CAS module defaults this to the username your CAS server provided. Any value placed here will overwrite what the CAS module provides.'),
      '#size' => 60,
      '#config_target' => 'cas_attributes.settings:field.mappings.name',
    ];

    $form['field']['mappings']['mail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('The <a href="@link">settings page for the main CAS module</a> defines the default value for e-mail. Any value placed here will overwrite what the CAS module provides.', ['@link' => Url::fromRoute('cas.settings')->toString()]),
      '#size' => 60,
      '#config_target' => 'cas_attributes.settings:field.mappings.mail',
    ];

    foreach ($this->entityFieldManager->getFieldDefinitions('user', 'user') as $name => $definition) {
      if (!empty($definition->getTargetBundle())) {
        switch ($definition->getType()) {
          case 'string':
          case 'list_string':
          case 'integer':
            $form['field']['mappings'][$name] = [
              '#type' => 'textfield',
              '#title' => $definition->getLabel(),
              '#config_target' => "cas_attributes.settings:field.mappings.$name",
              '#size' => 60,
              '#description' => $this->t('The account field with name %field_name.', ['%field_name' => $definition->getName()]),
            ];
            break;
        }
      }
    }

    $form['role'] = [
      '#type' => 'details',
      '#title' => $this->t('User Role Mappings'),
      '#description' => $this->t('Configure settings for assigning roles to users during login/registration based on CAS attribute values.'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    $form['role']['sync_frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('When should role mappings be applied to the user?'),
      '#options' => SyncFrequency::asOptions(),
      '#config_target' => 'cas_attributes.settings:role.sync_frequency',
    ];

    $form['role']['deny_login_no_match'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny login if no roles are mapped'),
      '#description' => $this->t('If enabled, users will not be able to login via CAS unless at least one role is assigned based on the mappings below.'),
      '#config_target' => 'cas_attributes.settings:role.deny_login_no_match',
    ];

    $form['role']['deny_registration_no_match'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Deny registration if no roles are mapped'),
      '#description' => $this->t('If enabled, users will not be able to auto-register via CAS unless at least one role is assigned based on the mappings below.'),
      '#config_target' => 'cas_attributes.settings:role.deny_registration_no_match',
    ];

    $form['role']['mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('CAS Role Mappings'),
      '#description' => $this->t("Each role mapping is a relationship between a role that is to be granted, an attribute name, an attribute value to match, and a method to use for comparison."),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];

    // Build a list of roles as select options.
    $this->roleOptions = array_map(
      fn(RoleInterface $role) => $role->label(),
      array_filter(
        $this->entityTypeManager->getStorage('user_role')->loadMultiple(),
        fn(RoleInterface $role) => $role->id() !== RoleInterface::AUTHENTICATED_ID,
      ),
    );

    // Add existing mappings to the form.
    foreach ($config->get('role.mappings') as $delta => $mapping) {
      $form['role']['mappings'][$delta] = $this->generateRoleMappingFormElements($mapping);
      $form['role']['mappings'][$delta]['delete'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Remove this mapping?'),
      ];
    }

    // Always add an empty row to allow adding a new mapping.
    $form['role']['mappings'][] = $this->generateRoleMappingFormElements();

    return parent::buildForm($form, $form_state);
  }

  /**
   * Generates a form elements for describing a role mapping.
   *
   * @return array
   *   The form elements for the mapping.
   */
  protected function generateRoleMappingFormElements(array $mapping = []): array {
    $elements = [
      '#type' => 'fieldset',
      '#title' => $this->t('Role Mapping'),
    ];
    $elements['rid'] = [
      '#type' => 'select',
      '#title' => $this->t('Role to Assign'),
      '#options' => $this->roleOptions,
      '#default_value' => $mapping['rid'] ?? NULL,
    ];
    $elements['attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Name'),
      '#description' => $this->t('See a <a href="@link">list of available attributes</a> for the currently logged in user (do not provide a token here, use the actual attribute name). Case does not matter.', ['@link' => Url::fromRoute('cas_attributes.available_attributes')->toString()]),
      '#size' => 30,
      '#default_value' => $mapping['attribute'] ?? NULL,
    ];
    $elements['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attribute Value'),
      '#size' => 30,
      '#default_value' => $mapping['value'] ?? NULL,
    ];
    $elements['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison Method'),
      '#options' => [
        'exact_single' => $this->t('Exact (Single)'),
        'exact_any' => $this->t('Exact (Any)'),
        'contains_any' => $this->t('Contains'),
        'regex_any' => $this->t('Regex'),
      ],
      '#description' => $this->t("
        The 'Exact (Single)' method passes if the attribute value has one value only and it matches the given string exactly.
        The 'Exact (Any)' method passes if any item in the attribute value array matches the given string exactly.
        The 'Contains' method passes if any item in the attribute value array contains the given string within it anywhere.
        The 'Regex' method passes if any item in the attribute value array passes the regular expression provided.
      "),
      '#default_value' => $mapping['method'] ?? NULL,
    ];
    $elements['negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate'),
      '#description' => $this->t('When checked, the specified role will be applied to the user if the attribute comparison fails to match. This can be useful if you want to assign a role based on the lack of some attribute value.'),
      '#default_value' => $mapping['negate'] ?? NULL,
    ];
    $elements['remove_without_match'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove role from user if match fails?'),
      '#description' => $this->t('IMPORTANT! If enabled, this will also remove the role if it was manually assigned to the user.'),
      '#default_value' => $mapping['remove_without_match'] ?? NULL,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $roleMappings = array_values(
      // Remove the 'delete' key from mappings.
      array_map(
        fn(array $mapping): array => array_diff_key($mapping, ['delete' => TRUE]),
        // Only keep non-delete and well-formed mappings.
        array_filter(
          $form_state->getValue(['role', 'mappings']),
          fn(array $mapping): bool => empty($mapping['delete']) && !empty($mapping['rid']) && !empty($mapping['attribute']) && !empty($mapping['value']),
        ),
      )
    );
    $form_state->setValue(['role', 'mappings'], $roleMappings);

    // Set #config_target on role mappings after we've cleanup the submission.
    $map = $form_state->get(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP) ?? [];
    $map['cas_attributes.settings']['role.mappings'] = ['role', 'mappings'];
    $form_state->set(static::CONFIG_KEY_TO_FORM_ELEMENT_MAP, $map);
    $form['role']['mappings']['#config_target'] = 'cas_attributes.settings:role.mappings';

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return ['cas_attributes.settings'];
  }

}
