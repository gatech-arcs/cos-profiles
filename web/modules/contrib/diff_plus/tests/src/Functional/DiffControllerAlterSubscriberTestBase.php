<?php

namespace Drupal\Tests\diff_plus\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test cases for the diff controller alter subscriber.
 */
abstract class DiffControllerAlterSubscriberTestBase extends BrowserTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'diff',
    'diff_plus',
  ];

  /**
   * A user with the author role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $author;

  /**
   * A user with the editor role.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->author = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'view article revisions',
    ], 'author');

    $this->editor = $this->drupalCreateUser([
      'create article content',
      'edit any article content',
      'view article revisions',
      'access user profiles',
    ], 'editor');

    // Create a filter format for the body field.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ]);
    $filtered_html_format->save();
  }

}
