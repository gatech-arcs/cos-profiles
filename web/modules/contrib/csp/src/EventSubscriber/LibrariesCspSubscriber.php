<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\LibraryPolicyBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP Policies based on library definitions.
 */
class LibrariesCspSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter', 0];
    return $events;
  }

  /**
   * Construct a CSP policy event subscriber for library assets.
   */
  public function __construct(
    private readonly LibraryPolicyBuilder $libraryPolicyBuilder,
  ) {

  }

  /**
   * Apply sources from library assets to a policy.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The policy alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void {
    $response = $alterEvent->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheTags(['library_info']);
    }

    $policy = $alterEvent->getPolicy();

    $libraryDirectives = $this->libraryPolicyBuilder->getSources();

    foreach ($libraryDirectives as $directiveName => $directiveValues) {
      if (!empty($directiveValues)) {
        $policy->fallbackAwareAppendIfEnabled($directiveName, $directiveValues);
      }
    }
  }

}
