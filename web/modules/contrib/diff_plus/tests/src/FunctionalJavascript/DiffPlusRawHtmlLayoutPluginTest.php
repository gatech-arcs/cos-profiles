<?php

namespace Drupal\Tests\diff_plus\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Contains test cases for the raw html diff layout.
 *
 * @group diff_plus
 */
class DiffPlusRawHtmlLayoutPluginTest extends WebDriverTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable the layout plugin.
    $diff_settings = $this->config('diff.settings')->get();
    $diff_settings['general_settings']['layout_plugins']['raw_html'] = [
      'enabled' => TRUE,
      'weight' => 8,
    ];

    $this
      ->config('diff.settings')
      ->setData($diff_settings)
      ->save();

    // Create a filter format for the body field.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
    ]);
    $filtered_html_format->save();

    // Create a content type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Create a node and a pair of revisions.
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
      'body' => [
        'value' => '<p>This is a test!</p>',
        'format' => 'filtered_html',
      ],
    ]);

    $node->set('body', [
      'value' => '<p>This is a test!</p><p>This is also a test!</p>',
      'format' => 'filtered_html',
    ]);
    $node->setNewRevision();
    $node->save();
  }

  /**
   * Test case for the Raw HTML diff layout.
   */
  public function testRawHtmlLayoutPlugin() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/node/1/revisions/view/1/2/raw_html');

    $page = $this->getSession()->getPage();

    $diff_preview = $page->find('css', '[data-diff-plus-preview]');
    static::assertNotNull($diff_preview);

    $insertion = $diff_preview->find('css', '.d2h-ins .d2h-code-side-line');
    static::assertSame('+       <p>This is also a test!</p>', $insertion->getText());
  }

}
