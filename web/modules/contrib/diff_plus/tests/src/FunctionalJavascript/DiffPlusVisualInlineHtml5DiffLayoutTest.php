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
class DiffPlusVisualInlineHtml5DiffLayoutTest extends WebDriverTestBase {

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
    $diff_settings['general_settings']['layout_plugins']['visual_inline_html5'] = [
      'enabled' => TRUE,
      'weight' => 8,
    ];

    $this
      ->config('diff.settings')
      ->setData($diff_settings)
      ->save();

    // Create a filter format for the body field.
    $filtered_html_format = FilterFormat::create([
      'format' => 'unrestricted_html',
      'name' => 'Unrestricted HTML',
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
        'format' => 'unrestricted_html',
      ],
    ]);

    $node->set('body', [
      'value' => <<<BODY
<p>This is a test!</p>
<p style="color:red">This is a test too!</p>
<script id="test-script">alert('I should be stripped out');</script>
BODY,
      'format' => 'unrestricted_html',
    ]);
    $node->setNewRevision();
    $node->save();
  }

  /**
   * Test case for the Raw HTML diff layout.
   */
  public function testVisualInlineHtml5LayoutPlugin() {
    $this->drupalLogin($this->drupalCreateUser([], NULL, TRUE));
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline_html5');
    $page = $this->getSession()->getPage();
    $insertion = $page->find('css', '.diffins');
    static::assertSame('This is a test too!', $insertion->getText());

    // Ensure that the style is not stripped by default.
    static::assertNotNull($page->find('css', '[style="color:red"]'));

    $this->config('diff_plus.settings')
      ->set('visual_html5_preserve_inline_styles', FALSE)
      ->save();

    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline_html5');
    $page = $this->getSession()->getPage();

    // Ensure that the style is now stripped.
    static::assertNull($page->find('css', '[style="color:red"]'));

    static::assertNull($page->find('css', 'script[id="test-script"]'));
  }

}
