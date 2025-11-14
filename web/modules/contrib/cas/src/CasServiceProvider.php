<?php

declare(strict_types=1);

namespace Drupal\cas;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\cas\DependencyInjection\DeprecatedEventConstants;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

@trigger_error('The ' . __NAMESPACE__ . '\CasServiceProvider is deprecated in cas:3.0.0 and is removed from cas:3.1.0. No replacement is provided. See https://www.drupal.org/node/3462792', E_USER_DEPRECATED);

/**
 * Adds a compiler pass to detect deprecated event constants.
 *
 * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. No replacement is
 *   provided.
 *
 * @see https://www.drupal.org/node/3462792
 */
class CasServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // Act after RegisterEventSubscribersPass.
    // @see \Drupal\Core\DependencyInjection\Compiler\RegisterEventSubscribersPass
    // @see \Drupal\Core\CoreServiceProvider::register()
    $container->addCompilerPass(new DeprecatedEventConstants(), PassConfig::TYPE_AFTER_REMOVING, -10);
  }

}
