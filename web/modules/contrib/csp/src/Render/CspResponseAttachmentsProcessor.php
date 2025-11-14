<?php

namespace Drupal\csp\Render;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;

/**
 * Decorator for the core HtmlResponseAttachmentsProcessor service.
 */
class CspResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * Keys for use in #attached.
   */
  private const KEYS = ['csp', 'csp_hash', 'csp_nonce'];

  /**
   * Construct an Attachments Processor decorator.
   *
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $htmlResponseAttachmentsProcessor
   *   The core HtmlResponseAttachmentsProcessor.
   */
  public function __construct(
    protected AttachmentsResponseProcessorInterface $htmlResponseAttachmentsProcessor,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    $originalAttachments = $response->getAttachments();
    $cspAttached = array_intersect_key($originalAttachments, array_flip(self::KEYS));

    if (empty($cspAttached)) {
      return $this->htmlResponseAttachmentsProcessor->processAttachments($response);
    }

    // Extract CSP data which core's processor cannot handle.
    $html_response = clone $response;
    $html_response->setAttachments(array_diff_key($originalAttachments, $cspAttached));

    // Call decorated processor for all other attachments.
    $processed_html_response = $this->htmlResponseAttachmentsProcessor->processAttachments($html_response);

    // Restore Csp data.
    $csp_response = clone $processed_html_response;
    $csp_response->setAttachments(array_merge($processed_html_response->getAttachments(), $cspAttached));

    return $csp_response;
  }

}
