<?php

namespace Drupal\Tests\fences\Kernel;

/**
 * The base class for field output tests.
 *
 * @group fences
 */
interface FieldOutputTestBaseInterface {

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupNoLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function onlyFieldTagNoLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function onlyFieldTagWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldAndLabelTagWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function classesAndTagsWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldAndFieldItemTagWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function defaultFieldWithLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupNoLabelItemsWrapperOnlyExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldTagItemsWrapperNoLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupWithLabelAndItemsWrapperExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldTagWithLabelAndItemsWrapperExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldAndLabelTagWithLabelAndItemsWrapperExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function defaultFieldDefaultItemsWrapperNoLabelExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldItemsWrapperAndLabelAllClassesSetExpectedSingle();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupNoLabelItemsWrapperOnlyExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldFieldItemAndItemsWrapperTagNoLabelExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldFieldItemItemsWrapperAndLabelTagWithLabelExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupWithLabelAndItemsWrapperExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function fieldAndItemsWrapperTagWithLabelExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupNoLabelItemTagOnlyExpectedMultiple();

  /**
   * Defines the expected output for the various settings in @fieldTestCases().
   */
  public static function noFieldMarkupWithLabelItemTagOnlyExpectedMultiple();

}
