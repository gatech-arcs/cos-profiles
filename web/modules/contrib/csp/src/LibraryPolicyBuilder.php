<?php

namespace Drupal\csp;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Utility\Error;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Service to build policy information for libraries.
 */
class LibraryPolicyBuilder implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Static cache of library source information for each extension.
   *
   * This reduces lookup calls to the database when generating information for
   * an extension, or when retrieving data for multiple libraries in an
   * extension.
   *
   * @var array<string, array<string, array<string, string[]>>>
   */
  protected array $librarySourcesCache;

  /**
   * Constructs a new Library Parser.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Module Handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The Theme Handler service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The Library Discovery Collector service.
   */
  public function __construct(
    protected CacheBackendInterface $cache,
    protected ModuleHandlerInterface $moduleHandler,
    protected ThemeHandlerInterface $themeHandler,
    protected LibraryDiscoveryInterface $libraryDiscovery,
  ) {

  }

  /**
   * Retrieve all sources required for the active theme.
   *
   * @return array<string, string[]>
   *   An array of sources keyed by type.
   */
  public function getSources(): array {
    $cid = implode(':', [
      'csp',
      'sources',
    ]);

    if (($cacheItem = $this->cache->get($cid))) {
      return $cacheItem->data;
    }

    $extensions = array_merge(
      ['core'],
      array_keys($this->moduleHandler->getModuleList()),
      array_keys($this->themeHandler->listInfo())
    );

    $sources = [];

    foreach ($extensions as $extensionName) {
      $extensionSources = $this->getExtensionSources($extensionName);
      $sources = NestedArray::mergeDeep($sources, $extensionSources);
    }

    foreach (array_keys($sources) as $type) {
      sort($sources[$type]);
      $sources[$type] = array_unique($sources[$type]);
    }

    $this->cache->set($cid, $sources, Cache::PERMANENT, [
      'library_info',
      'config:core.extension',
    ]);

    return $sources;
  }

  /**
   * Get the required sources for an extension.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array<string, string[]>
   *   An array of sources keyed by type.
   */
  protected function getExtensionSources(string $extension): array {
    $cid = implode(':', ['csp', 'extension', $extension]);

    $cacheItem = $this->cache->get($cid);
    if ($cacheItem) {
      return $cacheItem->data;
    }

    $sources = [];

    try {
      $moduleLibraries = $this->libraryDiscovery->getLibrariesByExtension($extension);
    }
    catch (\Exception $e) {
      // Ignore invalid library definitions.
      // @see \Drupal\Core\Asset\LibraryDiscoveryParser::buildByExtension()
      $this->logger->warning(Error::DEFAULT_ERROR_MESSAGE, Error::decodeException($e));
      $moduleLibraries = [];
    }

    foreach ($moduleLibraries as $libraryName => $libraryInfo) {
      $librarySources = $this->getLibrarySources($extension, $libraryName);
      $sources = NestedArray::mergeDeep($sources, $librarySources);
    }

    $this->cache->set($cid, $sources, Cache::PERMANENT, [
      'library_info',
    ]);

    return $sources;
  }

  /**
   * Get the required sources for a single library.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $name
   *   The name of a registered library to retrieve.
   *
   * @return array<string, string[]>
   *   An array of sources keyed by type.
   */
  protected function getLibrarySources(string $extension, string $name): array {
    $cid = implode(':', ['csp', 'libraries', $extension]);

    if (!isset($this->librarySourcesCache[$extension])) {
      $cacheItem = $this->cache->get($cid);
      if ($cacheItem) {
        $this->librarySourcesCache[$extension] = $cacheItem->data;
      }
    }

    if (isset($this->librarySourcesCache[$extension][$name])) {
      return $this->librarySourcesCache[$extension][$name];
    }

    $libraryInfo = $this->libraryDiscovery->getLibraryByName($extension, $name);
    $sources = [];

    foreach ($libraryInfo['js'] as $jsInfo) {
      if (
        $jsInfo['type'] == 'external'
        &&
        !empty($jsInfo['data'])
        &&
        ($host = self::getHostFromUri($jsInfo['data']))
      ) {
        $sources['script-src'][] = $host;
        $sources['script-src-elem'][] = $host;
      }
    }
    foreach ($libraryInfo['css'] as $cssInfo) {
      if (
        $cssInfo['type'] == 'external'
        &&
        !empty($cssInfo['data'])
        &&
        ($host = self::getHostFromUri($cssInfo['data']))
      ) {
        $sources['style-src'][] = $host;
        $sources['style-src-elem'][] = $host;
      }
    }

    $this->librarySourcesCache[$extension][$name] = $sources;
    $this->cache->set($cid, $this->librarySourcesCache[$extension], Cache::PERMANENT, [
      'library_info',
    ]);

    return $this->librarySourcesCache[$extension][$name];
  }

  /**
   * Get host info from a URI.
   *
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The host info.
   */
  public static function getHostFromUri(string $uri): string {
    $uri = new Uri($uri);
    $host = $uri->getHost();

    // Only include scheme if restricted to HTTPS.
    if ($uri->getScheme() === 'https') {
      $host = 'https://' . $host;
    }
    if (($port = $uri->getPort())) {
      $host .= ':' . $port;
    }
    return $host;
  }

}
