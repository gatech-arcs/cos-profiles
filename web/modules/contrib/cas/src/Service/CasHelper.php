<?php

declare(strict_types=1);

namespace Drupal\cas\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Token;
use Psr\Log\LogLevel;

/**
 * Utility and helper methods.
 */
class CasHelper {

  /**
   * SSL configuration to use the system's CA bundle to verify CAS server.
   *
   * @var int
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\SslCertificateVerification::Default instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const CA_DEFAULT = 0;

  /**
   * SSL configuration to use provided file to verify CAS server.
   *
   * @var int
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\SslCertificateVerification::Custom instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const CA_CUSTOM = 1;

  /**
   * SSL Configuration to not verify CAS server.
   *
   * @var int
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *    \Drupal\cas\Model\SslCertificateVerification::None instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const CA_NONE = 2;

  /**
   * Event type identifier for the CasPreUserLoadEvent.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreUserLoadEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreUserLoadEvent
   */
  const EVENT_PRE_USER_LOAD = 'cas.pre_user_load';

  /**
   * Event type identifier for the CasPreUserLoadRedirectEvent event.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreUserLoadRedirectEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreUserLoadRedirectEvent
   */
  const EVENT_PRE_USER_LOAD_REDIRECT = 'cas.pre_user_load.redirect';

  /**
   * Event type identifier for the CasPreRegisterEvent.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreRegisterEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreRegisterEvent
   */
  const EVENT_PRE_REGISTER = 'cas.pre_register';

  /**
   * Event type identifier for the CasPreLoginEvent.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreLoginEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreLoginEvent
   */
  const EVENT_PRE_LOGIN = 'cas.pre_login';

  /**
   * Event type identifier for CasPreRedirectEvent.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreRedirectEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreRedirectEvent
   */
  const EVENT_PRE_REDIRECT = 'cas.pre_redirect';

  /**
   * Event to modify CAS server config before it's used to validate a ticket.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class:
   *   CasPreValidateServerConfigEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreValidateServerConfigEvent
   */
  const EVENT_PRE_VALIDATE_SERVER_CONFIG = 'cas.pre_validate_server_config';

  /**
   * Event type identifier for pre validation events.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPreValidateEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPreValidateEvent
   */
  const EVENT_PRE_VALIDATE = 'cas.pre_validate';

  /**
   * Event type identifier for events fired after service validation.
   *
   * @var string
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPostValidateEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPostValidateEvent
   */
  const EVENT_POST_VALIDATE = 'cas.post_validate';

  /**
   * Event type identifier for events fired after login has completed.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use the fully
   *   qualified name of the event class: CasPostLoginEvent::class.
   *
   * @see https://www.drupal.org/node/3462792
   * @see \Drupal\cas\Event\CasPostLoginEvent
   */
  const EVENT_POST_LOGIN = 'cas.post_login';

  /**
   * Indicates gateway redirect performed server-side.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\GatewayMethod::ServerSide instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const GATEWAY_SERVER_SIDE = 'server_side';

  /**
   * Indicates gateway redirect performed client-side.
   *
   * @deprecated in cas:3.0.0 and is removed from cas:3.1.0. Use
   *   \Drupal\cas\Model\GatewayMethod::ClientSide instead.
   *
   * @see https://www.drupal.org/node/3462792
   */
  const GATEWAY_CLIENT_SIDE = 'client_side';

  /**
   * A list of routes we should never trigger CAS login redirect on.
   */
  const IGNORABLE_AUTO_LOGIN_ROUTES = [
    'cas.service',
    'cas.proxyCallback',
    'cas.login',
    'cas.legacy_login',
    'cas.logout',
    'image.style_private',
    'image.style_public',
    'system.cron',
    'system.css_asset',
    'system.js_asset',
    'user.logout',
    'user.logout.http',
  ];

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
    protected Token $token,
  ) {}

  /**
   * Wrap Drupal's normal logger.
   *
   * This allows us to only log debug messages if configured to do so.
   *
   * @param mixed $level
   *   The message to log.
   * @param string $message
   *   The error message.
   * @param array $context
   *   The context.
   */
  public function log(mixed $level, string $message, array $context = []): void {
    // Back out of logging if it's a debug message and we're not configured
    // to log those types of messages. This helps keep the drupal log clean
    // on busy sites.
    if ($level == LogLevel::DEBUG && !$this->getCasSetting('advanced.debug_log')) {
      return;
    }
    $this->loggerFactory->get('cas')->log($level, $message, $context);
  }

  /**
   * Returns a translated configurable message given the message config key.
   *
   * @param string $key
   *   The message config key.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The customized message or an empty string.
   *
   * @throws \InvalidArgumentException
   *   If the passed key don't match a config entry.
   */
  public function getMessage(string $key): MarkupInterface|string {
    assert($key && is_string($key));
    $message = $this->getCasSetting($key);
    if ($message === NULL || !is_string($message)) {
      throw new \InvalidArgumentException("Invalid key '$key'");
    }

    // Empty string.
    if (!$message) {
      return '';
    }

    return new FormattableMarkup(Xss::filter($this->token->replace($message)), []);
  }

  /**
   * Returns the CAS module settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The CAS module settings.
   */
  public function getCasSettings(): ImmutableConfig {
    return $this->configFactory->get('cas.settings');
  }

  /**
   * Returns a setting from the CAS module settings.
   *
   * @param string $key
   *   The configuration key.
   *
   * @return mixed
   *   The CAS module settings.
   */
  public function getCasSetting(string $key): mixed {
    return $this->getCasSettings()->get($key);
  }

}
