<?php

namespace Drupal\Tests\diff_plus\Functional;

/**
 * Test cases for the diff controller alter subscriber.
 *
 * @group diff_plus
 */
class DiffControllerAlterSubscriberTest extends DiffControllerAlterSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a node and a pair of revisions.
    $node = $this->drupalCreateNode([
      'title' => 'Y2k38 Node!!!',
      'type' => 'article',
      'body' => [
        'value' => '<p>This is a test!</p>',
        'format' => 'filtered_html',
      ],
      'uid' => $this->author,
      'revision_log' => 'The end is near!!!',
    ]);

    // Happy y2k38.  I hope you're ready.
    $node->setRevisionCreationTime(2147483446)->save();

    $node->set('body', [
      'value' => '<p>This is a test!</p><p>Is...anyone still alive?</p>',
      'format' => 'filtered_html',
    ]);
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
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline');

    $page = $this->getSession()->getPage();
    $from_revision = $page->find('css', '.diff-plus-ui__from-revision')->getText();

    $this->assertStringContainsString('From revision 1 (Published)', $from_revision);
    $this->assertStringContainsString('Authored by author', $from_revision);
    $this->assertStringContainsString('January 19th, 2038 2:10pm', $from_revision);
    $this->assertStringContainsString('The end is near!!!', $from_revision);

    $to_revision = $page->find('css', '.diff-plus-ui__to-revision')->getText();

    $this->assertStringContainsString('To revision 2 (Published)', $to_revision);
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
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline');

    $page = $this->getSession()->getPage();
    $from_revision = $page->find('css', '.diff-plus-ui__from-revision')->getText();

    $this->assertStringContainsString('From revision 1 (Published)', $from_revision);
    $this->assertStringContainsString('Authored by author', $from_revision);
    $this->assertStringContainsString('January 19th, 2038 2:10pm', $from_revision);
    $this->assertStringContainsString('The end is near!!!', $from_revision);

    $to_revision = $page->find('css', '.diff-plus-ui__to-revision')->getText();

    $this->assertStringContainsString('To revision 2 (Published)', $to_revision);
    $this->assertStringContainsString('Authored by editor', $to_revision);
    $this->assertStringContainsString('January 19th, 2038 2:14pm', $to_revision);
    $this->assertStringContainsString('Are we still here?', $to_revision);

    $this->config('diff_plus.settings')->set('enhance_diff_ui', FALSE)->save();
    $this->drupalGet('/node/1/revisions/view/1/2/visual_inline');

    $page = $this->getSession()->getPage();
    $this->assertNull($page->find('css', '.diff-plus-ui'));
  }

}
