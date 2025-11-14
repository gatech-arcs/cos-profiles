<?php

namespace Drupal\Tests\diff_plus\Functional;

use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Test cases for the diff controller alter subscriber.
 *
 * @group diff_plus
 */
class DiffControllerAlterSubscriberContentModerationTest extends DiffControllerAlterSubscriberTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'diff',
    'diff_plus',
    'workflows',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'article');
    // Create a node and a pair of revisions with moderation states.
    $node = $this->drupalCreateNode([
      'title' => 'Y2k38 Node!!!',
      'type' => 'article',
      'body' => [
        'value' => '<p>This is a test!</p>',
        'format' => 'filtered_html',
      ],
      'uid' => $this->author,
    ]);

    $node->setRevisionUser($this->author);
    $node->setRevisionLogMessage('The end is near!!!');

    // Happy y2k38.  I hope you're ready.
    $node->setRevisionCreationTime(2147483446);
    $node->save();

    $node->set('body', [
      'value' => '<p>This is a test!</p><p>Is...anyone still alive?</p>',
      'format' => 'filtered_html',
    ]);
    $node->set('moderation_state', 'published');

    $node->setNewRevision();
    $node->setRevisionUser($this->editor);
    $node->setRevisionCreationTime(2147483647);
    $node->setRevisionLogMessage('Are we still here?');
    $node->save();
  }

  /**
   * Test case for the author persona.
   */
  public function testAuthorPersona() {
    $this->drupalLogin($this->author);

    $this->drupalGet('/node/1/revisions/view/2/3/visual_inline');

    $page = $this->getSession()->getPage();
    $from_revision = $page->find('css', '.diff-plus-ui__from-revision')->getText();

    $this->assertStringContainsString('From revision 2 (Draft)', $from_revision);
    $this->assertStringContainsString('Authored by author', $from_revision);
    $this->assertStringContainsString('January 19th, 2038 2:10pm', $from_revision);
    $this->assertStringContainsString('The end is near!!!', $from_revision);

    $to_revision = $page->find('css', '.diff-plus-ui__to-revision')->getText();

    $this->assertStringContainsString('To revision 3 (Published)', $to_revision);
    $this->assertStringContainsString('Authored by a user who you do not have permission to view.', $to_revision);
    $this->assertStringContainsString('January 19th, 2038 2:14pm', $to_revision);
    $this->assertStringContainsString('Are we still here?', $to_revision);

    $this->config('diff_plus.settings')->set('enhance_diff_ui', FALSE)->save();
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline');

    $page = $this->getSession()->getPage();
    $this->assertNull($page->find('css', '.diff-plus-ui'));
  }

  /**
   * Test case for the editor persona.
   */
  public function testEditorPersona() {
    $this->drupalLogin($this->editor);
    $this->drupalGet('/node/1/revisions/view/2/3/visual_inline');

    $page = $this->getSession()->getPage();
    $from_revision = $page->find('css', '.diff-plus-ui__from-revision')->getText();

    $this->assertStringContainsString('From revision 2 (Draft)', $from_revision);
    $this->assertStringContainsString('Authored by author', $from_revision);
    $this->assertStringContainsString('January 19th, 2038 2:10pm', $from_revision);
    $this->assertStringContainsString('The end is near!!!', $from_revision);

    $to_revision = $page->find('css', '.diff-plus-ui__to-revision')->getText();

    $this->assertStringContainsString('To revision 3 (Published)', $to_revision);
    $this->assertStringContainsString('Authored by editor', $to_revision);
    $this->assertStringContainsString('January 19th, 2038 2:14pm', $to_revision);
    $this->assertStringContainsString('Are we still here?', $to_revision);

    $this->config('diff_plus.settings')->set('enhance_diff_ui', FALSE)->save();
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline');

    $page = $this->getSession()->getPage();
    $this->assertNull($page->find('css', '.diff-plus-ui'));

  }

}
