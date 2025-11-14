<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\Nonce;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to add CSP headers to responses.
 */
class ResponseCspSubscriber implements EventSubscriberInterface {

  /**
   * The CSP settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Constructs a new ResponseSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Event Dispatcher Service.
   * @param \Drupal\csp\Nonce $nonce
   *   The nonce service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    protected EventDispatcherInterface $eventDispatcher,
    protected Nonce $nonce,
  ) {
    $this->config = $configFactory->get('csp.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE] = [
      // Nonce value needs to be added before settings are rendered to the page
      // by \Drupal\Core\EventSubscriber\HtmlResponseSubscriber.
      ['applyDrupalSettingsNonce', 1],
      // Policy needs to be generated after placeholder library info is bubbled
      // up and rendered to the page.
      ['onKernelResponse'],
    ];
    return $events;
  }

  /**
   * Add a nonce value to drupalSettings.
   *
   * The value is always added, but only available to scripts if a library
   * included on the response has csp/nonce or core/drupalSettings as a
   * dependency.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Response Event.
   */
  public function applyDrupalSettingsNonce(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!($response instanceof AttachmentsInterface)) {
      return;
    }

    $response->addAttachments([
      'drupalSettings' => [
        'csp' => [
          'nonce' => $this->nonce->getValue(),
        ],
      ],
    ]);
  }

  /**
   * Add Content-Security-Policy header to response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The Response event.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();
    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheableDependency($this->config);
    }

    foreach (['report-only', 'enforce'] as $policyType) {
      $policy = new Csp();
      $policy->reportOnly($policyType == 'report-only');

      if (!$this->config->get($policyType . '.enable')) {
        // Remove any default policy set by core.
        $response->headers->remove($policy->getHeaderName());
        continue;
      }

      $this->eventDispatcher->dispatch(
        new PolicyAlterEvent($policy, $response),
        CspEvents::POLICY_ALTER
      );

      if (($headerValue = $policy->getHeaderValue())) {
        $response->headers->set($policy->getHeaderName(), $headerValue);
      }
    }
  }

}
