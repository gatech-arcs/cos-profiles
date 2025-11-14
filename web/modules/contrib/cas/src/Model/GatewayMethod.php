<?php

declare(strict_types=1);

namespace Drupal\cas\Model;

/**
 * Gateway method.
 */
enum GatewayMethod: string implements OptionsInterface {

  // Indicates gateway redirect performed server-side.
  case ServerSide = 'server_side';

  // Indicates gateway redirect performed client-side.
  case ClientSide = 'client_side';

  /**
   * {@inheritdoc}
   */
  public static function getAsOptions(): array {
    return [
      GatewayMethod::ServerSide->value => t('Server-side redirect. Faster, but disables page caching on configured paths.'),
      GatewayMethod::ClientSide->value => t('Client-side redirect (using JavaScript). Slower, but works with all page caching. Not compatible with "Every page request" option above.'),
    ];
  }

}
