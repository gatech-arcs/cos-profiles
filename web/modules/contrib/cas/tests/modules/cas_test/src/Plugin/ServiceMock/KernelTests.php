<?php

declare(strict_types=1);

namespace Drupal\cas_test\Plugin\ServiceMock;

use Drupal\Core\Plugin\PluginBase;
use Drupal\http_request_mock\ServiceMockPluginInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Intercepts any HTTP request made to example.com.
 *
 * @ServiceMock(
 *   id = "kernel_tests",
 *   label = @Translation("Used in kernel tests"),
 *   weight = 0,
 * )
 */
class KernelTests extends PluginBase implements ServiceMockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request, array $options): bool {
    return $request->getUri()->getHost() === 'example.com' && in_array($request->getUri()->getPath(), [
      '/serviceValidate',
      '/proxy',
      '/service',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(RequestInterface $request, array $options): ResponseInterface {
    $path = $request->getUri()->getPath();

    switch ($path) {
      case '/serviceValidate':
        parse_str($request->getUri()->getQuery(), $query);
        $ticket = $query['ticket'] ?? NULL;
        $success = $ticket === 'ST-foobar';
        $authentication = $success ? 'Success' : 'Failure';

        $body[] = '<cas:serviceResponse xmlns:cas="http://example.com/cas">';
        $body[] = "  <cas:authentication$authentication>";

        if ($success) {
          $body[] = '    <cas:user>john.doe</cas:user>';
        }
        else {
          $body[] = match ($ticket) {
            'ST-invalid' => 'Ticket ST-invalid not recognized'
          };
        }

        if (!empty($query['pgtUrl'])) {
          $body[] = '  <cas:proxyGrantingTicket>PGTIOU-foobar</cas:proxyGrantingTicket>';
        }

        $body[] = "  </cas:authentication$authentication>";
        $body[] = '</cas:serviceResponse>';
        return new Response(200, [], implode("\n", $body));

      case '/proxy':
        $response = <<<RESPONSE
        <cas:serviceResponse xmlns:cas='http://example.com/cas'>
          <cas:proxySuccess>
            <cas:proxyTicket>PT-proxytest</cas:proxyTicket>
          </cas:proxySuccess>
        </cas:serviceResponse>
        RESPONSE;
        return new Response(200, [], $response);

      case '/service':
        return new Response(200, [
          'Content-type' => 'text/html',
          'Set-Cookie' => 'SESSION=foobar',
        ]);
    }

    return new Response(400);
  }

}
