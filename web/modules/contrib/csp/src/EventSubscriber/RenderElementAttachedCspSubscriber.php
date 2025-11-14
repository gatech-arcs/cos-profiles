<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\PolicyHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policy with attached data from render elements.
 */
class RenderElementAttachedCspSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected PolicyHelper $policyHelper,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CspEvents::POLICY_ALTER] = [
      ['applyDirectives'],
      // Nonces and Hashes must be applied after other alters.
      ['applyNoncesAndHashes', -32],
    ];
    return $events;
  }

  /**
   * Apply any directive sources from render elements.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The policy alter event.
   */
  public function applyDirectives(PolicyAlterEvent $alterEvent): void {
    $policy = $alterEvent->getPolicy();
    $response = $alterEvent->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    $attachments = $response->getAttachments();

    /**
     * @var string $directive
     * @var string[] $sources
     */
    foreach ($attachments['csp'] ?? [] as $directive => $sources) {
      assert(
        empty(preg_grep("<'(nonce|" . implode('|', Csp::HASH_ALGORITHMS) . ")-.+'>", $sources)),
        "Use csp_hash or csp_nonce to attach a hash or nonce to a render element"
      );

      if (!empty($sources)) {
        $policy->fallbackAwareAppendIfEnabled($directive, $sources);
      }
    }
  }

  /**
   * Apply nonce and hashes if required by any render elements.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The policy alter event.
   */
  public function applyNoncesAndHashes(PolicyAlterEvent $alterEvent): void {
    $policy = $alterEvent->getPolicy();
    $response = $alterEvent->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    $attachments = $response->getAttachments();

    foreach ($attachments['csp_nonce'] ?? [] as $directiveType => $fallback) {
      assert(
        preg_match('<^(script|style)(?:-src(?:-elem)?)?$>', $directiveType),
        "Nonces can only be added to script or style element directives"
      );

      // Should only be 'script' or 'style', but accept *-src or *-src-elem too.
      if (preg_match('<^(script|style)(?:-src(?:-elem)?)?$>', $directiveType, $matches)) {
        $this->policyHelper->appendNonce($policy, $matches[1], $fallback);
      }
    }

    foreach ($attachments['csp_hash'] ?? [] as $directive => $hashes) {
      assert(
        preg_match('<^(script|style)(?:-src)?(?:-(elem|attr))?$>', $directive),
        "Hashes can only be added to script or style directives"
      );

      preg_match('<^(script|style)(?:-src)?(?:-(elem|attr))?$>', $directive, $matches);
      $directiveType = $matches[1];
      $directiveSubType = $matches[2] ?? 'elem';

      foreach ($hashes as $hash => $fallback) {
        $this->policyHelper->appendHash($policy, $directiveType, $directiveSubType, $fallback, $hash);
      }
    }
  }

}
