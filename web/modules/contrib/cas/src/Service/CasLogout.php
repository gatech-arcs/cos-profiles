<?php

declare(strict_types=1);

namespace Drupal\cas\Service;

use Drupal\Core\Database\Connection;
use Drupal\cas\Exception\CasSloException;
use Psr\Log\LogLevel;

/**
 * Provides a default implementation for 'cas.logout' service.
 */
class CasLogout {

  public function __construct(
    protected readonly CasHelper $casHelper,
    protected Connection $connection,
  ) {}

  /**
   * Handles a single-log-out request from a CAS server.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   *
   * @throws \Drupal\cas\Exception\CasSloException
   *   If the logout data could not be parsed.
   */
  public function handleSlo(string $data): void {
    $this->casHelper->log(LogLevel::DEBUG, "Attempting to handle single-log-out request.");

    // Only look up tickets if they were stored to begin with.
    if (!$this->casHelper->getCasSetting('logout.enable_single_logout')) {
      $this->casHelper->log(LogLevel::DEBUG, "Aborting single-log-out handling; it's not enabled in the CAS settings.");
      return;
    }

    $service_ticket = $this->getServiceTicketFromData($data);
    $this->casHelper->log(
      LogLevel::DEBUG,
      'Service ticket %ticket extracted from single-log-out request.',
      ['%ticket' => $service_ticket]
    );

    // Look up the session ID by the service ticket, then load up that
    // session and destroy it.
    $sid = $this->lookupSessionIdByServiceTicket($service_ticket);
    if (!$sid) {
      $this->casHelper->log(
        LogLevel::DEBUG,
        'No matching session found for %ticket',
        ['%ticket' => $service_ticket]
      );
      return;
    }

    $this->destroySession($sid);
    $this->removeSessionMapping($sid);

    $this->casHelper->log(LogLevel::DEBUG, "Single-log-out request completed successfully.");
  }

  /**
   * Load up the session and destroy it.
   *
   * @param string $sid
   *   The ticket id to destroy.
   *
   * @codeCoverageIgnore
   */
  protected function destroySession(string $sid): void {
    session_id($sid);
    session_start();
    session_destroy();
    session_write_close();
  }

  /**
   * Parse the SLO SAML and return the service ticket.
   *
   * @param string $data
   *   The raw data posted to us from the CAS server.
   *
   * @return string
   *   The service ticket to log out.
   *
   * @throws \Drupal\cas\Exception\CasSloException
   *   If the logout data could not be parsed.
   */
  private function getServiceTicketFromData(string $data): string {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";

    if ($dom->loadXML($data) === FALSE) {
      throw new CasSloException("SLO data from CAS server is not valid.");
    }

    $session_elements = $dom->getElementsByTagName('SessionIndex');
    if ($session_elements->length == 0) {
      throw new CasSloException("SLO data from CAS server is not valid.");
    }

    $session_element = $session_elements->item(0);
    return $session_element->nodeValue;
  }

  /**
   * Lookup Session ID by CAS service ticket.
   *
   * @param string $ticket
   *   A service ticket value from CAS to lookup in the database.
   *
   * @return string|null
   *   The session ID corresponding to the session ticket.
   */
  private function lookupSessionIdByServiceTicket(string $ticket): ?string {
    $result = $this->connection->select('cas_login_data', 'c')
      ->fields('c', ['plainsid'])
      ->condition('ticket', $ticket)
      ->execute()
      ->fetch();
    if (!empty($result)) {
      return $result->plainsid;
    }
    else {
      return NULL;
    }
  }

  /**
   * Remove the SLO session mapping data for the passed in session ID.
   *
   * @param string $sid
   *   The user's session ID.
   */
  private function removeSessionMapping(string $sid): void {
    $this->connection->delete('cas_login_data')
      ->condition('plainsid', $sid)
      ->execute();
  }

}
