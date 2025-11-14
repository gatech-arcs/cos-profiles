<?php

declare(strict_types=1);

namespace Drupal\cas;

/**
 * Provides tools to build the redirects.
 */
class CasRedirectData {

  /**
   * Indicates whether the redirect will occur.
   */
  protected bool $willRedirect = TRUE;

  /**
   * Indicates whether the redirect response can be cached.
   */
  protected bool $isCacheable = FALSE;

  /**
   * Cache tags to apply to cacheable redirect responses.
   *
   * By default, this contains the config cache tag for the cas module settings,
   * even though this object itself, in its initial state, does not depend on
   * that configuration.
   *
   * Since #3508713, the default value is redundant for the main place where
   * this class is used in cas module, because the config object is explicitly
   * added as a dependency to the response.
   *
   * However, there might be 3rd party code that relies on the default cache tag
   * being present.
   *
   * @var string[]
   */
  protected array $cacheTags = ['config:cas.settings'];

  /**
   * Cache contexts to apply to cacheable redirect responses.
   *
   * We need to vary the redirect response based on the URL because:
   * 1. The site domain is included in the service parameter in the redirect.
   * 2. Parameters on the URL are passed along as query params to the service
   * URL as well.
   *
   * @var string[]
   */
  protected array $cacheContexts = ['url'];

  /**
   * Constructs a new CAS redirect data instance.
   *
   * @param array $serviceParameters
   *   Parameters to add to the service_url when requesting redirect.
   * @param array $redirectParameters
   *   Parameters used when redirecting to the CASE server.
   */
  public function __construct(
    protected array $serviceParameters = [],
    protected array $redirectParameters = [],
  ) {}

  /**
   * Set a redirection parameter.
   *
   * Sets a redirect parameter that will later be used in the redirection
   * request. NULL values will cause the parameter to be unset, but not
   * when they are required.
   *
   * @param string $key
   *   Key of parameter to set.
   * @param mixed $value
   *   Value of the parameter to set.
   */
  public function setParameter(string $key, mixed $value): void {
    if (empty($value)) {
      unset($this->redirectParameters[$key]);
    }
    else {
      $this->redirectParameters[$key] = $value;
    }
  }

  /**
   * Returns the redirect parameter specified by $key.
   *
   * @param string $key
   *   Parameter to select.
   *
   * @return mixed
   *   Value of the parameter.
   */
  public function getParameter(string $key): mixed {
    return $this->redirectParameters[$key] ?? NULL;
  }

  /**
   * Returns all parameters that will be used in redirect.
   *
   * @return array
   *   Array representation of all redirect parameters.
   */
  public function getAllParameters(): array {
    return $this->redirectParameters;
  }

  /**
   * Set a service parameter.
   *
   * @param string $key
   *   Service parameter to set.
   * @param mixed $value
   *   Value of service parameter to set.
   */
  public function setServiceParameter(string $key, mixed $value): void {
    if (empty($value)) {
      unset($this->serviceParameters[$key]);
    }
    else {
      $this->serviceParameters[$key] = $value;
    }
  }

  /**
   * Returns the redirect parameter specified by $key.
   *
   * @param string $key
   *   Parameter to select.
   *
   * @return string|null
   *   Value of the attribute.
   */
  public function getServiceParameter(string $key): ?string {
    return $this->serviceParameters[$key] ?? NULL;
  }

  /**
   * Get all service parameters.
   *
   * @return array
   *   Array containing all service parameters.
   */
  public function getAllServiceParameters(): array {
    return $this->serviceParameters;
  }

  /**
   * Indicate that the redirect response is cacheable.
   *
   * @param bool $cacheable
   *   TRUE to set the redirect as cacheable, FALSE otherwise.
   */
  public function setIsCacheable(bool $cacheable): void {
    if ($cacheable) {
      $this->isCacheable = TRUE;
    }
    else {
      $this->isCacheable = FALSE;
    }
  }

  /**
   * Return if the redirect response is cacheable or not.
   *
   * @return bool
   *   TRUE if the redirect response is cacheable, FALSE otherwise.
   */
  public function getIsCacheable(): bool {
    return $this->isCacheable;
  }

  /**
   * Check if a redirect is being allowed.
   *
   * @return bool
   *   TRUE implies that a redirect will occur.
   *   FALSE implies that no redirect will occur.
   */
  public function willRedirect(): bool {
    return $this->willRedirect;
  }

  /**
   * Force the redirection to occur (may still be prevented).
   */
  public function forceRedirection(): void {
    $this->willRedirect = TRUE;
  }

  /**
   * Prevent Redirection form occurring (may still be forced).
   */
  public function preventRedirection(): void {
    $this->willRedirect = FALSE;
  }

  /**
   * Set the cache tags that will be added to the redirect response.
   *
   * @param array $cache_tags
   *   The cache tags.
   */
  public function setCacheTags(array $cache_tags): void {
    $this->cacheTags = $cache_tags;
  }

  /**
   * Get the cache tags for the redirect response.
   *
   * @return array
   *   The cache tags.
   */
  public function getCacheTags(): array {
    return $this->cacheTags;
  }

  /**
   * Set the cache contexts for the redirect response.
   *
   * @param array $cache_contexts
   *   The cache contexts.
   */
  public function setCacheContexts(array $cache_contexts): void {
    $this->cacheContexts = $cache_contexts;
  }

  /**
   * Get the cache contexts for the redirect response.
   *
   * @return array
   *   The cache contexts.
   */
  public function getCacheContexts(): array {
    return $this->cacheContexts;
  }

}
