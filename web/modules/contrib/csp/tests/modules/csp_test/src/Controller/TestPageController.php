<?php

namespace Drupal\csp_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\csp\Csp;
use Drupal\csp\NonceBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for test_page_test routes.
 */
class TestPageController extends ControllerBase {

  public function __construct(
    protected NonceBuilder $nonceBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('csp.nonce_builder'),
    );
  }

  /**
   * Returns a test page and sets the title.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function testSettingsNoNonce(): array {
    return [
      '#title' => 'Test page',
      '#markup' => 'Test page text.',
    ];
  }

  /**
   * Returns a test page and sets the title.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function testSettingsNonce(): array {
    return [
      '#title' => 'Test page',
      '#markup' => 'Test page text.',
      '#attached' => [
        'library' => ['csp/nonce'],
      ],
    ];
  }

  /**
   * Returns a test page with attached CSP directives.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function testElementDirectives(): array {
    return [
      '#title' => 'Test page',
      '#markup' => 'Test page text.',
      '#attached' => [
        'csp' => [
          'img-src' => ['https://img.example.com'],
          'font-src' => ['https://fonts.example.com'],
        ],
      ],
    ];
  }

  /**
   * Returns a test page with a placeholdered nonce.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function testElementNonce(): array {
    return [
      '#title' => 'Test page',
      '#markup' => 'Test page text. <div id="nonce">' . $this->nonceBuilder->getPlaceholderKey() . '</div>',
      '#attached' => [
        'csp_nonce' => [
          'script' => Csp::POLICY_UNSAFE_INLINE,
        ],
        'placeholders' => [
          $this->nonceBuilder->getPlaceholderKey() => [
            '#lazy_builder' => ['csp.nonce_builder:renderNonce', []],
          ],
        ],
      ],
    ];
  }

  /**
   * Returns a test page with an attached hash.
   *
   * @return array<string, mixed>
   *   A render array.
   */
  public function testElementHash(): array {
    return [
      '#title' => 'Test page',
      '#markup' => 'Test page text.',
      '#attached' => [
        'csp_hash' => [
          'script-src-elem' => [
            "'sha256-m0zKW3SgFyV1D9aL5SVP9sTjV8ymQ9XirnpSfOsqCFk='" => Csp::POLICY_UNSAFE_INLINE,
          ],
        ],
      ],
    ];
  }

}
