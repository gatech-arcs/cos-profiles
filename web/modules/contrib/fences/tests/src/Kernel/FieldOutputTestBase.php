<?php

namespace Drupal\Tests\fences\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\fences\Traits\StripWhitespaceTrait;

/**
 * The base class for field output tests.
 *
 * @group fences
 */
abstract class FieldOutputTestBase extends KernelTestBase implements FieldOutputTestBaseInterface {

  use StripWhitespaceTrait;

  /**
   * The test field name for the "cardinality" = 1 field.
   *
   * @var string
   */
  protected const FIELD_NAME_SINGLE = 'field_test';

  /**
   * The test field name for the "cardinality" = CARDINALITY_UNLIMITED field.
   *
   * @var string
   */
  protected const FIELD_NAME_MULTIPLE = 'field_test_multiple';

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test';

  /**
   * The test entity used for testing output.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * The entity display under test.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $entityViewDisplay;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'field_test',
    'fences',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $this->installEntitySchema($this->entityTypeId);
    $this->installEntitySchema('filter_format');
    $this->renderer = \Drupal::service('renderer');

    // Setup a field and an entity display.
    EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ])->save();

    // Create the single cardinality field:
    FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME_SINGLE,
      'entity_type' => $this->entityTypeId,
      'type' => 'text',
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => self::FIELD_NAME_SINGLE,
      'bundle' => $this->entityTypeId,
      'label' => 'Field Test',
    ])->save();

    // Create the multiple cardinality field:
    FieldStorageConfig::create([
      'field_name' => self::FIELD_NAME_MULTIPLE,
      'entity_type' => $this->entityTypeId,
      'type' => 'text',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => self::FIELD_NAME_MULTIPLE,
      'bundle' => $this->entityTypeId,
      'label' => 'Field Test Multiple',
      'translatable' => FALSE,
    ])->save();

    $this->entityViewDisplay = EntityViewDisplay::load('entity_test.entity_test.default');

    // Create a test entity with a test value.
    $this->entity = EntityTest::create();
    $this->entity->set(self::FIELD_NAME_SINGLE, 'lorem ipsum');
    $this->entity->set(self::FIELD_NAME_MULTIPLE, [
      'test value 1',
      'test value 2',
      'test value 3',
    ]);
    $this->entity->save();

    // Set the default filter format.
    FilterFormat::create([
      'format' => 'test_format',
      'name' => $this->randomMachineName(),
    ])->save();
    $this->config('filter.settings')
      ->set('fallback_format', 'test_format')
      ->save();
  }

}
