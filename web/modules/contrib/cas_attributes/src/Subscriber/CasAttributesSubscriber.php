<?php

declare(strict_types=1);

namespace Drupal\cas_attributes\Subscriber;

use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas_attributes\Model\SyncFrequency;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a CasAttributesSubscriber.
 */
class CasAttributesSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly Token $tokenService,
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CasPreRegisterEvent::class][] = ['onPreRegister', -1];
    $events[CasPreLoginEvent::class][] = ['onPreLogin', 20];
    $events[CasPostLoginEvent::class][] = ['onPostLogin'];
    return $events;
  }

  /**
   * Subscribe to the CasPreRegisterEvent.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The CasPreAuthEvent containing property information.
   */
  public function onPreRegister(CasPreRegisterEvent $event): void {
    if ($this->getSetting('field.sync_frequency') !== SyncFrequency::Never->value) {
      // Map fields.
      $field_mappings = $this->getFieldMappings($event->getCasPropertyBag()->getAttributes());
      if (!empty($field_mappings)) {
        $event->setPropertyValues($field_mappings);
      }
    }

    if ($this->getSetting('role.sync_frequency') !== SyncFrequency::Never->value) {
      // Map roles.
      $roleMappingResults = $this->doRoleMapCheck($event->getCasPropertyBag()->getAttributes());

      if (empty($roleMappingResults['add']) && $this->getSetting('role.deny_registration_no_match')) {
        $event->cancelAutomaticRegistration();
      }
      else {
        // Add/remove roles from the user.
        $existingProperties = $event->getPropertyValues();
        $rolesForUser = [];
        if (!empty($existingProperties['roles'])) {
          $rolesForUser = $existingProperties['roles'];
        }

        $rolesForUser = array_diff($rolesForUser, $roleMappingResults['remove']);
        $rolesForUser = array_merge($rolesForUser, $roleMappingResults['add']);
        $rolesForUser = array_unique($rolesForUser);
        $event->setPropertyValue('roles', $rolesForUser);
      }
    }
  }

  /**
   * Subscribe to the CasPreLoginEvent.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The CasPreAuthEvent containing account and property information.
   */
  public function onPreLogin(CasPreLoginEvent $event): void {
    $account = $event->getAccount();

    // Map fields.
    if ($this->getSetting('field.sync_frequency') === SyncFrequency::EveryLogin->value) {
      $field_mappings = $this->getFieldMappings($event->getCasPropertyBag()->getAttributes());
      if (!empty($field_mappings)) {
        // If field already has data, only set new value if configured to
        // overwrite existing data.
        $overwrite = $this->getSetting('field.overwrite');
        foreach ($field_mappings as $field_name => $field_value) {
          if ($overwrite || empty($account->get($field_name))) {
            $account->set($field_name, $field_value);
          }
        }
      }
    }

    // Map roles.
    $roleMappingResults = $this->doRoleMapCheck($event->getCasPropertyBag()->getAttributes());
    if ($this->getSetting('role.sync_frequency') === SyncFrequency::EveryLogin->value) {
      foreach ($roleMappingResults['remove'] as $rid) {
        $account->removeRole($rid);
      }
      foreach ($roleMappingResults['add'] as $rid) {
        $account->addRole($rid);
      }
    }

    if (empty($roleMappingResults['add']) && $this->getSetting('role.deny_login_no_match')) {
      $event->cancelLogin();
    }
  }

  /**
   * Maps fields to the pre-defined CAS token values.
   *
   * @param array $casAttributes
   *   The list of CAS attributes for the user logging in.
   *
   * @return array
   *   Field data.
   */
  protected function getFieldMappings(array $casAttributes): array {
    $mappings = $this->getSetting('field.mappings');
    if (empty($mappings)) {
      return [];
    }

    $field_data = [];

    foreach ($mappings as $field_name => $attribute_token) {
      $result = trim($this->tokenService->replace(
        $attribute_token,
        ['cas_attributes' => $casAttributes],
        ['clear' => TRUE]
      ));
      $result = html_entity_decode($result);

      // Only update the fields if there is data to set.
      if (!empty($result)) {
        $field_data[$field_name] = $result;
      }
    }

    return $field_data;
  }

  /**
   * Determine which roles should be added/removed based on attributes.
   *
   * @param array $attributes
   *   The attributes associated with the user.
   *
   * @return array
   *   Array containing two keys:
   *   - add: the list of RIDs to add to the user
   *   - remove: the list of RIDs to remove from the user
   */
  protected function doRoleMapCheck(?array $attributes = NULL): array {
    $role_map = $this->getSetting('role.mappings');
    if (empty($role_map)) {
      return [
        'add' => [],
        'remove' => [],
      ];
    }

    $rolesToAdd = [];
    $rolesToRemove = [];

    if (is_array($attributes)) {
      // Change attribute names to lower case. We do this for attributes used
      // as tokens as well so this keeps their usage consistent no where
      // they are used.
      $attributes = array_change_key_case($attributes, CASE_LOWER);
    }

    foreach ($role_map as $condition) {
      // Force attr name to lowercase before comparing as we lowered the case
      // of the attributes array as well. This allows case differences between
      // the attribute names returned from the server and the ones configured
      // to not matter.
      $conditionAttribute = strtolower($condition['attribute']);

      // Attribute not found; don't map role.
      if (!isset($attributes[$conditionAttribute])) {
        continue;
      }
      $attributeValue = $attributes[$conditionAttribute];
      if (!is_array($attributeValue)) {
        $attributeValue = [$attributeValue];
      }
      $valueToMatch = $condition['value'];

      $matched = FALSE;
      switch ($condition['method']) {
        case 'exact_single':
          $matched = $this->checkRoleMatchExactSingle($attributeValue, $valueToMatch);
          break;

        case 'exact_any':
          $matched = $this->checkRoleMatchExactAny($attributeValue, $valueToMatch);
          break;

        case 'contains_any':
          $matched = $this->checkRoleMatchContainsAny($attributeValue, $valueToMatch);
          break;

        case 'regex_any':
          $matched = $this->checkRoleMatchRegexAny($attributeValue, $valueToMatch);
        default:
      }

      if (isset($condition['negate']) && $condition['negate']) {
        $matched = !$matched;
      }

      if ($matched) {
        $rolesToAdd[] = $condition['rid'];
      }
      elseif ($condition['remove_without_match']) {
        $rolesToRemove[] = $condition['rid'];
      }
    }

    return [
      'add' => $rolesToAdd,
      'remove' => $rolesToRemove,
    ];
  }

  /**
   * Check if attributes match using the 'exact_single' method.
   *
   * This method checks that the the attribute values match exactly.
   * The attribute is expected to be a single value, and not a multi value
   * attribute.
   *
   * @param array $attributeValue
   *   The actual attribute value.
   * @param string $valueToMatch
   *   The attribute value to compare against.
   *
   * @return bool
   *   TRUE if there's a match, FALSE otherwise.
   */
  protected function checkRoleMatchExactSingle(array $attributeValue, string $valueToMatch): bool {
    // The expectation for this method is that the attribute is not multi-value.
    if (count($attributeValue) > 1) {
      return FALSE;
    }

    $value = array_shift($attributeValue);
    if ($value === $valueToMatch) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check if attributes match using the 'exact_any' method.
   *
   * This is the same as the 'exact_single' method, except it will check if any
   * of the elements in a multi-value attribute match the expected value
   * exactly.
   *
   * @param array $attributeValue
   *   The actual attribute value.
   * @param string $valueToMatch
   *   The attribute value to compare against.
   *
   * @return bool
   *   TRUE if there's a match, FALSE otherwise.
   */
  protected function checkRoleMatchExactAny(array $attributeValue, string $valueToMatch): bool {
    foreach ($attributeValue as $value) {
      if ($value === $valueToMatch) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check if attributes match using the 'contains_any' method.
   *
   * Works by checking if any item in attribute value contains the value to
   * match as a substring.
   *
   * @param array $attributeValue
   *   The actual attribute value.
   * @param string $valueToMatch
   *   The attribute value to compare against.
   *
   * @return bool
   *   TRUE if there's a match, FALSE otherwise.
   */
  protected function checkRoleMatchContainsAny(array $attributeValue, string $valueToMatch): bool {
    foreach ($attributeValue as $value) {
      if (strpos($value, $valueToMatch) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check if attributes match using the 'regex_any' method.
   *
   * Each item in attribute array is checked with a regex.
   *
   * @param array $attributeValue
   *   The actual attribute value.
   * @param string $regex
   *   The regular expression pattern.
   *
   * @return bool
   *   TRUE if there's a match, FALSE otherwise.
   */
  protected function checkRoleMatchRegexAny(array $attributeValue, string $regex): bool {
    foreach ($attributeValue as $value) {
      if (@preg_match($regex, $value)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Save attributes to user session if sitewide token support is enabled.
   *
   * @param \Drupal\cas\Event\CasPostLoginEvent $casPostLoginEvent
   *   The post login event from CAS.
   */
  public function onPostLogin(CasPostLoginEvent $casPostLoginEvent): void {
    if ($this->getSetting('sitewide_token_support')) {
      $session = $this->requestStack->getCurrentRequest()->getSession();

      $attributes = $casPostLoginEvent->getCasPropertyBag()->getAttributes();
      $token_allowed_attributes = $this->getSetting('token_allowed_attributes');

      if (!empty($token_allowed_attributes)) {
        // Change to all lowercase facilitate case-insensitive matching.
        $token_allowed_attributes = array_map('mb_strtolower', $token_allowed_attributes);
        foreach ($attributes as $key => $value) {
          if (!in_array(mb_strtolower($key), $token_allowed_attributes)) {
            unset($attributes[$key]);
          }
        }
      }

      $session->set('cas_attributes', $attributes);
    }
  }

  /**
   * Returns a CAS Attributes setting value.
   *
   * @param string $name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSetting(string $name): mixed {
    return $this->configFactory->get('cas_attributes.settings')->get($name);
  }

}
