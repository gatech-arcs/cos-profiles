<?php

declare(strict_types=1);

namespace Drupal\cas\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\cas\Service\CasHelper;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a controller for the 'cas.proxyCallback' route.
 */
class ProxyCallbackController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected Connection $connection,
    protected readonly RequestStack $requestStack,
    protected readonly CasHelper $casHelper,
  ) {}

  /**
   * Route callback for the ProxyGrantingTicket information.
   *
   * This function stores the incoming PGTIOU and pgtId parameters so that
   * the incoming response from the CAS Server can be looked up.
   */
  public function callback(): Response {
    $this->casHelper->log(LogLevel::DEBUG, 'Proxy callback processing started.');

    // @todo Check that request is coming from configured CAS server to avoid
    // filling up the table with bogus pgt values.
    $request = $this->requestStack->getCurrentRequest();

    // Check for both a pgtIou and pgtId parameter. If either is not present,
    // inform CAS Server of an error.
    if (!($request->query->get('pgtId') && $request->query->get('pgtIou'))) {
      $this->casHelper->log(LogLevel::ERROR, "Either pgtId or pgtIou parameters are missing from the request.");
      return new Response('Missing necessary parameters', 400);
    }
    else {
      // Store the pgtIou and pgtId in the database for later use.
      $pgt_id = $request->query->get('pgtId');
      $pgt_iou = $request->query->get('pgtIou');
      $this->storePgtMapping($pgt_iou, $pgt_id);
      $this->casHelper->log(
        LogLevel::DEBUG,
        "Storing pgtId %pgt_id with pgtIou %pgt_iou",
        ['%pgt_id' => $pgt_id, '%pgt_iou' => $pgt_iou]
      );
      // PGT stored properly, tell CAS Server to proceed.
      return new Response('OK', 200);
    }
  }

  /**
   * Store the pgtIou to pgtId mapping in the database.
   *
   * @param string $pgt_iou
   *   The pgtIou from CAS Server.
   * @param string $pgt_id
   *   The pgtId from the CAS server.
   *
   * @codeCoverageIgnore
   */
  protected function storePgtMapping(string $pgt_iou, string $pgt_id): void {
    $this->connection->insert('cas_pgt_storage')
      ->fields(
        ['pgt_iou', 'pgt', 'timestamp'],
        [$pgt_iou, $pgt_id, time()]
      )
      ->execute();
  }

}
