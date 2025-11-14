<?php

namespace Drupal\Tests\fences\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * A fences integration test.
 *
 * @group fences
 */
class IntegrationTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'node',
    'field',
    'field_ui',
    'fences',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'type' => 'article',
      'body' => 'Body field value.',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer node display',
      'edit fences formatter settings',
    ]);
    // User without "Edit fences formatter settings":
    $this->user = $this->drupalCreateUser([
      'access content',
      'administer node display',
    ]);
    $this->drupalLogin($this->adminUser);
  }

// @codingStandardsIgnoreStart
  /**
   * Test the basic settings through the UI.
   *
   * @todo This test currently fails, because the "$page->fillField" method call
   * tries to refer to stale elements, which causes a chromedriver error. For
   * more information, see
   * https://www.drupal.org/project/fences/issues/3411039#comment-15389732
   */
  // public function testBasicSettingsThroughUI() {
  //   $session = $this->assertSession();
  //   $page = $this->getSession()->getPage();
  //   $this->drupalGet('/admin/structure/types/manage/article/display');
  //   $page->pressButton('body_settings_edit');

  //   // Wait for the fences settings to appear:
  //   $this->assertNotNull($session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]'));

  //   // Hence we are using drupal states to hide the classes textfields, if one
  //   // of the tags is set to "none", we need to select the tags first:
  //   $page->selectFieldOption('fields[body][settings_edit_form][third_party_settings][fences][fences_field_tag]', 'article');
  //   $page->selectFieldOption('fields[body][settings_edit_form][third_party_settings][fences][fences_field_items_wrapper_tag]', 'div');
  //   $page->selectFieldOption('fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_tag]', 'code');
  //   $page->selectFieldOption('fields[body][settings_edit_form][third_party_settings][fences][fences_label_tag]', 'h2');
  //   $page->pressButton('Update');
  //   $this->assertNotNull($session->waitForElementRemoved('css', 'div[id*="edit-fields-body-settings-edit-form"]'));

  //   $page->pressButton('body_settings_edit');

  //   // Wait for the fences settings to appear:
  //   $this->assertNotNull($session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]'));

  //   $page->fillField('fields[body][label]', 'above');
  //   $page->fillField('fields[body][settings_edit_form][third_party_settings][fences][fences_field_classes]', 'my-field-class');
  //   $page->fillField('fields[body][settings_edit_form][third_party_settings][fences][fences_field_items_wrapper_classes]', 'my-field-items-class');
  //   $page->fillField('fields[body][settings_edit_form][third_party_settings][fences][fences_field_item_classes]', 'my-field-item-class');
  //   $page->fillField('fields[body][settings_edit_form][third_party_settings][fences][fences_label_classes]', 'my-label-class');
  //   $page->pressButton('Update');
  //   $this->assertNotNull($session->waitForElementRemoved('css', 'div[id*="edit-fields-body-settings-edit-form"]'));
  //   $page->pressButton('edit-submit');

  //   $this->drupalGet('/node/' . $this->node->id());
  //   $article = $session->elementExists('css', '.field--name-body');
  //   $this->assertTrue($article->hasClass('my-field-class'), 'Custom field class is present.');
  //   $label = $session->elementExists('css', 'div.my-label-class', $article);
  //   $this->assertSame($label->getText(), 'Body', 'Field label is found in expected HTML element.');
  //   $body = $session->elementExists('css', 'div.my-field-items-class > code.my-field-item-class > p', $article);
  //   $this->assertSame($body->getText(), 'Body field value.', 'Field text is found in expected HTML element.');
  // }
// @codingStandardsIgnoreEnd

  /**
   * Test the basic settings.
   */
  public function testBasicSettings() {
    $session = $this->assertSession();

    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('body', [
        'type' => 'text_default',
        'settings' => [],
        'third_party_settings' => [
          'fences' => [
            'fences_field_tag' => 'article',
            'fences_field_classes' => 'my-field-class',
            'fences_field_items_wrapper_tag' => 'div',
            'fences_field_items_wrapper_classes' => 'my-field-items-class',
            'fences_field_item_tag' => 'code',
            'fences_field_item_classes' => 'my-field-item-class',
            'fences_label_tag' => 'h2',
            'fences_label_classes' => 'my-label-class',
          ],
        ],
      ])
      ->save();

    $this->drupalGet('/node/' . $this->node->id());
    $article = $session->elementExists('css', '.field--name-body');
    $this->assertTrue($article->hasClass('my-field-class'), 'Custom field class is present.');
    $label = $session->elementExists('css', 'h2.my-label-class', $article);
    $this->assertSame($label->getText(), 'Body', 'Field label is found in expected HTML element.');
    $body = $session->elementExists('css', 'div.my-field-items-class > code.my-field-item-class > p', $article);
    $this->assertSame($body->getText(), 'Body field value.', 'Field text is found in expected HTML element.');
  }

  /**
   * Tests if the max length attribute isn't present in the settings inputs.
   */
  public function testMaxLengthRemoved() {
    $session = $this->assertSession();

    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('body', [
        'type' => 'text_default',
        'settings' => [],
        'third_party_settings' => [
          'fences' => [
            'fences_field_tag' => 'article',
            'fences_field_classes' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz',
            'fences_field_items_wrapper_tag' => 'div',
            'fences_field_items_wrapper_classes' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz',
            'fences_field_item_tag' => 'code',
            'fences_field_item_classes' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz',
            'fences_label_tag' => 'h2',
            'fences_label_classes' => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz',
          ],
        ],
      ])
      ->save();

    $this->drupalGet('/node/' . $this->node->id());
    $article = $session->elementExists('css', '.field--name-body');
    $this->assertTrue($article->hasClass('abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz'), 'Custom field class is present.');
    $label = $session->elementExists('css', 'h2.abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz', $article);
    $this->assertSame($label->getText(), 'Body', 'Field label is found in expected HTML element.');
    $body = $session->elementExists('css', 'div.abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz > code.abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz > p', $article);
    $this->assertSame($body->getText(), 'Body field value.', 'Field text is found in expected HTML element.');
  }

  /**
   * Tests the "edit fences formatter settings" permission.
   */
  public function testEditFencesFormatterSettingsPermission() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to display page and see if the fences settings are there:
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    $session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $session->elementExists('css', 'details[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
    $this->drupalLogout();
    // Login with a user without the 'edit fences formatter settings'
    // permission and see if the settings are NOT displayed anymore:
    $this->drupalLogin($this->user);
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $page->pressButton('edit-fields-body-settings-edit');
    $session->waitForElementVisible('css', 'div[id*="edit-fields-body-settings-edit-form"]');
    $session->elementNotExists('css', 'details[id*="edit-fields-body-settings-edit-form-third-party-settings-fences"]');
  }

}
