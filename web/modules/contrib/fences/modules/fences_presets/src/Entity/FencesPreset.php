<?php

declare(strict_types=1);

namespace Drupal\fences_presets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fences_presets\FencesPresetInterface;

/**
 * Defines the fences preset entity type.
 *
 * @ConfigEntityType(
 *   id = "fences_preset",
 *   label = @Translation("Fences Preset"),
 *   label_collection = @Translation("Fences Presets"),
 *   label_singular = @Translation("Fences Preset"),
 *   label_plural = @Translation("Fences Presets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Fences Preset",
 *     plural = "@count Fences Presets",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\fences_presets\FencesPresetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fences_presets\Form\FencesPresetForm",
 *       "edit" = "Drupal\fences_presets\Form\FencesPresetForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "fences_preset",
 *   admin_permission = "administer fences_preset",
 *   links = {
 *     "collection" = "/admin/structure/fences-preset",
 *     "add-form" = "/admin/structure/fences-preset/add",
 *     "edit-form" = "/admin/structure/fences-preset/{fences_preset}",
 *     "delete-form" = "/admin/structure/fences-preset/{fences_preset}/delete",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "status",
 *     "label",
 *     "description",
 *     "field_tag",
 *     "field_classes",
 *     "field_items_wrapper_tag",
 *     "field_items_wrapper_classes",
 *     "field_item_tag",
 *     "field_item_classes",
 *     "label_tag",
 *     "label_classes",
 *   },
 * )
 */
final class FencesPreset extends ConfigEntityBase implements FencesPresetInterface {

  /**
   * The ID.
   */
  protected string $id;

  /**
   * The label.
   */
  protected string $label;

  /**
   * The description.
   */
  protected string $description;

  /**
   * The field tag.
   */
  protected string $field_tag;

  /**
   * The field classes.
   */
  protected string $field_classes;

  /**
   * The field items wrapper tag.
   */
  protected string $field_items_wrapper_tag;

  /**
   * The field items wrapper classes.
   */
  protected string $field_items_wrapper_classes;

  /**
   * The field item tag.
   */
  protected string $field_item_tag;

  /**
   * The field item classes.
   */
  protected string $field_item_classes;

  /**
   * The label tag.
   */
  protected string $label_tag;

  /**
   * The label classes.
   */
  protected string $label_classes;

  /**
   * Get the value of id.
   */
  public function getId(): string {
    return $this->get('id');
  }

  /**
   * Get the value of label.
   */
  public function getLabel(): string {
    return $this->get('label');
  }

  /**
   * Get the value of description.
   */
  public function getDescription(): string {
    return $this->get('description');
  }

  /**
   * Get the value of field_tag.
   */
  public function getFieldTag(): string {
    return $this->get('field_tag');
  }

  /**
   * Get the value of field_classes.
   */
  public function getFieldClasses(): string {
    return $this->get('field_classes');
  }

  /**
   * Get the value of field_items_wrapper_tag.
   */
  public function getFieldItemsWrapperTag(): string {
    return $this->get('field_items_wrapper_tag');
  }

  /**
   * Get the value of field_items_wrapper_classes.
   */
  public function getFieldItemsWrapperClasses(): string {
    return $this->get('field_items_wrapper_classes');
  }

  /**
   * Get the value of field_item_tag.
   */
  public function getFieldItemTag(): string {
    return $this->get('field_item_tag');
  }

  /**
   * Get the value of field_item_classes.
   */
  public function getFieldItemClasses(): string {
    return $this->get('field_item_classes');
  }

  /**
   * Get the value of label_tag.
   */
  public function getLabelTag(): string {
    return $this->get('label_tag');
  }

  /**
   * Get the value of label_classes.
   */
  public function getLabelClasses(): string {
    return $this->get('label_classes');
  }

}
