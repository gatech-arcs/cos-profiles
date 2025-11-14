<?php

namespace Drupal\Tests\csp\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Wrap getConfigFactoryStub to provide cache metadata.
 */
trait ConfigFactoryCacheableMetadataTrait {

  /**
   * {@inheritdoc}
   *
   * Config is wrapped to provide cache metadata.
   *
   * @param array<string, array<string, mixed>> $configs
   *   An associative array of configuration settings whose keys are
   *   configuration object names and whose values are key => value arrays for
   *   the configuration object in question. Defaults to an empty array.
   */
  public function getConfigFactoryStub(array $configs = []): ConfigFactoryInterface {
    $parent = parent::getConfigFactoryStub($configs);

    $factory = $this->createMock(ConfigFactoryInterface::class);
    $factory->expects($this->any())
      ->method('get')
      ->willReturnCallback(function ($configName) use ($parent) {
        /** @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $parent->get($configName);
        $config->expects($this->any())
          ->method('getCacheContexts')
          ->willReturn([]);
        $config->expects($this->any())
          ->method('getCacheTags')
          ->willReturn(['config:' . $configName]);
        $config->expects($this->any())
          ->method('getCacheMaxAge')
          ->willReturn(Cache::PERMANENT);

        return $config;
      });

    return $factory;
  }

}
