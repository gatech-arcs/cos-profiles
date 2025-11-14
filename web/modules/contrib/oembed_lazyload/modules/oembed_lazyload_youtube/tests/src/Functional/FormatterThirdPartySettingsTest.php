<?php

namespace Drupal\Tests\oembed_lazyload_youtube\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Test cases pertaining to the third party formatter settings.
 *
 * @group oembed_lazyload_youtube
 */
class FormatterThirdPartySettingsTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'link',
    'media_test_oembed',
    'oembed_lazyload',
    'oembed_lazyload_youtube',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A media type entity.
   *
   * @var \Drupal\media\MediaTypeInterface
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {

    parent::setUp();

    $this->mediaType = $this->createMediaType('oembed:video');

    $source = $this->mediaType->getSource();
    $source_field = $source->getSourceFieldDefinition($this->mediaType);

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'media',
      'bundle' => $this->mediaType->id(),
      'mode' => 'full',
      'status' => TRUE,
    ]);

    $view_display->setComponent($source_field->getName(), [
      'type' => 'lazyload_oembed',
      'region' => 'content',
      'settings' => [
        'max_width' => 550,
        'max_height' => 310,
      ],
    ]);

    $view_display->save();
  }

  /**
   * Test case for the third party settings form.
   */
  public function testThirdPartySettingsForm() {

    $this->drupalLogin($this->adminUser);

    // Head over to the entity view display "full" override settings form.
    $this->drupalGet(
      Url::fromRoute('entity.entity_view_display.media.view_mode', [
        'media_type' => $this->mediaType->id(),
        'view_mode_name' => 'full',
      ])
    );
    $assert = $this->assertSession();
    $summary = $assert->elementExists('css', '.oembed-lazyload-youtube-settings')->getText();
    static::assertStringContainsString('YouTube settings', $summary);
    static::assertStringContainsString('YouTube branding will be visible on videos.', $summary);
    static::assertStringContainsString('The video will not be controllable via the YouTube IFrame API.', $summary);
    static::assertStringContainsString('Related videos from other channels may be shown.', $summary);
    static::assertStringNotContainsString('Access to the IFrame API will not be limited to the oembed iframe domain.', $summary);
    static::assertStringNotContainsString('Access to the IFrame API will be limited to the oembed iframe domain.', $summary);
    static::assertStringNotContainsString('The video will attempt to hide the video title and uploader prior to playing. This is a deprecated player parameter and may not work for every account, and may cease to work at any time!', $summary);

    $assert->elementExists('css', '[name="field_media_oembed_video_settings_edit"]')->click();
    $details = $assert->elementExists('css', '[data-drupal-selector="edit-fields-field-media-oembed-video-settings-edit-form-third-party-settings-oembed-lazyload-youtube-settings"]');
    $summary = $details->find('css', 'summary');
    static::assertSame('YouTube settings', $summary->getText());

    $autoplay = $details->findField('Attempt to auto-play the video');
    static::assertFalse($autoplay->isChecked());

    $modestbranding = $details->findField('Hide YouTube branding on player interface');
    static::assertFalse($modestbranding->isChecked());

    $enablejsapi = $details->findField('Allow video to be controlled via the YouTube IFrame API');
    static::assertFalse($enablejsapi->isChecked());

    $enablejsapi = $details->findField('Allow video to be controlled via the YouTube IFrame API');
    static::assertFalse($enablejsapi->isChecked());

    $origin = $details->findField('Only allow the oembed iframe domain to control the IFrame API (recommended)');
    static::assertFalse($origin->isChecked());

    $hideinfo = $details->findField('Hide the video title and uploader before the video starts playing');
    static::assertFalse($hideinfo->isChecked());

    $rel = $details->findField('Only show related videos from the same channel as the current video');
    static::assertFalse($rel->isChecked());

    $autoplay->check();
    $modestbranding->check();
    $enablejsapi->check();
    $origin->check();
    $hideinfo->check();
    $rel->check();

    $this->submitForm([], 'Update');

    $summary = $assert->elementExists('css', '.oembed-lazyload-youtube-settings')->getText();
    static::assertStringContainsString('YouTube settings', $summary);
    static::assertStringContainsString('YouTube branding will not be visible on videos.', $summary);
    static::assertStringContainsString('The video will be controllable via the YouTube IFrame API.', $summary);
    static::assertStringContainsString('Access to the IFrame API will be limited to the oembed iframe domain.', $summary);
    static::assertStringContainsString('The video will attempt to hide the video title and uploader prior to playing. This is a deprecated player parameter and may not work for every account, and may cease to work at any time!', $summary);
    static::assertStringContainsString('Only related videos from the same channel as the current video will be shown.', $summary);
  }

}
