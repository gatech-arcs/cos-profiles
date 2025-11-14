<?php

declare(strict_types=1);

namespace Drupal\cas\PageCache;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\cas\Model\GatewayMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures pages configured with gateway authentication are not cached.
 *
 * The logic we use to determine if a user should be redirected to gateway auth
 * is currently not compatible with page caching.
 */
class DenyCas implements ResponsePolicyInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ExecutableManagerInterface $conditionManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request): ?string {
    $config = $this->configFactory->get('cas.settings');
    if ($config->get('gateway.enabled') && $config->get('gateway.method') === GatewayMethod::ServerSide->value) {
      // User can indicate specific paths to enable (or disable) gateway mode.
      $condition = $this->conditionManager->createInstance('request_path', $config->get('gateway.paths'));
      assert($condition instanceof ExecutableInterface);
      if ($this->conditionManager->execute($condition)) {
        return static::DENY;
      }
    }
    return NULL;
  }

}
