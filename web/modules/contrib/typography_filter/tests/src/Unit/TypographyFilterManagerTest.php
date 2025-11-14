<?php

namespace Drupal\Tests\typography_filter\Unit;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\typography_filter\Services\TypographyFilterManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the typography filter service.
 *
 * @group typography_filter
 */
class TypographyFilterManagerTest extends UnitTestCase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|MockObject
   */
  protected LanguageManagerInterface|MockObject $languageManager;

  /**
   * The tested typography filter service.
   *
   * @var \Drupal\typography_filter\Services\TypographyFilterManager
   */
  protected TypographyFilterManager $typographyFilterManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->languageManager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'en']));
    $this->typographyFilterManager = new TypographyFilterManager($this->languageManager);
  }

  /**
   * Test HTML content fixes.
   */
  public function testFix(): void {
    $to_fix = <<<EOT
<h3>Test header-- nice.</h3>
<p>Here "<span>HTML in quote</span>" to fix...</p>
<div>"This text should be skipped"...</div>
EOT;

    $fixed = <<<EOT
<h3>Test header&mdash;nice.</h3>
<p>Here &ldquo;<span>HTML in quote</span>&rdquo; to fix&hellip;</p>
<div>"This text should be skipped"...</div>
EOT;

    $this->assertSame($fixed, $this->typographyFilterManager->fix($to_fix, [], '', ['div']));
  }

  /**
   * Test non HTML content fixes.
   */
  public function testFixString(): void {
    $to_fix = 'Here is a "pro tip(c)"!';
    $fixed = 'Here is a “pro tip©”!';
    $this->assertSame($fixed, $this->typographyFilterManager->fixString($to_fix, [
      'Trademark',
      'SmartQuotes',
    ]));
  }

}
