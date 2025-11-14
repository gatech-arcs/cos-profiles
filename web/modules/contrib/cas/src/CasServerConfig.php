<?php

declare(strict_types=1);

namespace Drupal\cas;

use Drupal\Core\Config\Config;
use Drupal\cas\Model\CasProtocolVersion;
use Drupal\cas\Model\HttpScheme;
use Drupal\cas\Model\SslCertificateVerification;

/**
 * Class CasServerConfig.
 *
 * A value object that represents server configuration for the CAS server.
 *
 * This object can is passed around in various CAS events, allowing modules
 * to modify details about the default CAS server config if needed.
 */
class CasServerConfig {

  /**
   * The CAS protocol version to use when interacting with the CAS server.
   */
  protected CasProtocolVersion $protocolVersion;

  /**
   * The HTTP scheme to use for the CAS server.
   */
  protected HttpScheme $httpScheme;

  /**
   * The HTTP hostname to use for the CAS server.
   */
  protected string $hostname;

  /**
   * The port number to use for the CAS server.
   */
  protected int $port;

  /**
   * The path where CAS can be interacted with on the CAS server.
   */
  protected string $path;

  /**
   * The cert verification method to use when interacting with the CAS server.
   */
  protected SslCertificateVerification $verify;

  /**
   * The path to the CA root cert bundle to use when validating server SSL cert.
   */
  protected string $customRootCertBundlePath;

  /**
   * Number of seconds to wait on CAS server response during requests.
   */
  protected int $connectionTimeout;

  /**
   * Initialize an object from the CAS module config.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object for the CAS module.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The initialized value object.
   */
  public static function createFromModuleConfig(Config $config): CasServerConfig {
    $obj = new self();
    $obj->setProtocolVersion(CasProtocolVersion::from($config->get('server.version')));
    $obj->setHttpScheme(HttpScheme::from($config->get('server.protocol')));
    $obj->setHostname($config->get('server.hostname'));
    $obj->setPort($config->get('server.port'));
    $obj->setPath($config->get('server.path'));
    $obj->setVerify(SslCertificateVerification::from($config->get('server.verify')));
    $obj->setCustomRootCertBundlePath($config->get('server.cert'));
    $obj->setConnectionTimeout($config->get('advanced.connection_timeout'));
    return $obj;
  }

  /**
   * Set the protocol version.
   *
   * @param \Drupal\cas\Model\CasProtocolVersion $version
   *   The version.
   */
  public function setProtocolVersion(CasProtocolVersion $version): void {
    $this->protocolVersion = $version;
  }

  /**
   * Get the protocol version.
   *
   * @return \Drupal\cas\Model\CasProtocolVersion
   *   The protocol version.
   */
  public function getProtocolVersion(): CasProtocolVersion {
    return $this->protocolVersion;
  }

  /**
   * Set the HTTP scheme.
   *
   * @param \Drupal\cas\Model\HttpScheme $scheme
   *   The scheme.
   */
  public function setHttpScheme(HttpScheme $scheme): void {
    $this->httpScheme = $scheme;
  }

  /**
   * Get HTTP scheme.
   *
   * @return \Drupal\cas\Model\HttpScheme
   *   The HTTP scheme.
   */
  public function getHttpScheme(): HttpScheme {
    return $this->httpScheme;
  }

  /**
   * Set hostname.
   *
   * @param string $hostname
   *   The hostname.
   */
  public function setHostname(string $hostname): void {
    $this->hostname = $hostname;
  }

  /**
   * Get hostname.
   *
   * @return string
   *   The hostname.
   */
  public function getHostname(): string {
    return $this->hostname;
  }

  /**
   * Set port.
   *
   * @param int $port
   *   The port.
   */
  public function setPort(int $port): void {
    $this->port = $port;
  }

  /**
   * Get port.
   *
   * @return int
   *   The port.
   */
  public function getPort(): int {
    return $this->port;
  }

  /**
   * Set path.
   *
   * @param string $path
   *   The path.
   */
  public function setPath(string $path): void {
    $this->path = $path;
  }

  /**
   * Get path.
   *
   * @return string
   *   The path.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Set SSL cert verification method.
   *
   * @param \Drupal\cas\Model\SslCertificateVerification $verify
   *   The verification method.
   */
  public function setVerify(SslCertificateVerification $verify): void {
    $this->verify = $verify;
  }

  /**
   * Get SSL cert verification method.
   *
   * @return \Drupal\cas\Model\SslCertificateVerification
   *   The SSL cert verification method.
   */
  public function getVerify(): SslCertificateVerification {
    return $this->verify;
  }

  /**
   * Set custom CA root cert bundle path.
   *
   * @param string $path
   *   The path.
   */
  public function setCustomRootCertBundlePath(string $path): void {
    $this->customRootCertBundlePath = $path;
  }

  /**
   * Get custom CA root cert bundle path.
   *
   * @return string
   *   The path.
   */
  public function getCustomRootCertBundlePath(): string {
    return $this->customRootCertBundlePath;
  }

  /**
   * Set connection timeout.
   *
   * @param int $timeout
   *   The timeout.
   */
  public function setConnectionTimeout(int $timeout): void {
    $this->connectionTimeout = $timeout;
  }

  /**
   * Get connection timeout.
   *
   * @return int
   *   The timeout.
   */
  public function getDirectConnectionTimeout(): int {
    return $this->connectionTimeout;
  }

  /**
   * Construct the base URL to the CAS server.
   *
   * @return string
   *   The base URL.
   */
  public function getServerBaseUrl(): string {
    $httpScheme = $this->getHttpScheme()->value;
    $url = $httpScheme . '://' . $this->getHostname();

    // Only append port if it's non standard.
    $port = $this->getPort();
    if (($httpScheme === 'http' && $port !== 80) || ($httpScheme === 'https' && $port !== 443)) {
      $url .= ':' . $port;
    }

    $url .= $this->getPath();
    $url = rtrim($url, '/') . '/';

    return $url;
  }

  /**
   * Gets config data for guzzle communications with the CAS server.
   *
   * @return array
   *   The guzzle connection options.
   */
  public function getCasServerGuzzleConnectionOptions(): array {
    return [
      'verify' => match ($this->getVerify()) {
        SslCertificateVerification::Custom => $this->getCustomRootCertBundlePath(),
        SslCertificateVerification::None => FALSE,
        SslCertificateVerification::Default => TRUE,
      },
      'timeout' => $this->getDirectConnectionTimeout(),
    ];
  }

}
