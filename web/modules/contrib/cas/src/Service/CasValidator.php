<?php

declare(strict_types=1);

namespace Drupal\cas\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\cas\CasPropertyBag;
use Drupal\cas\CasServerConfig;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\cas\Event\CasPreValidateEvent;
use Drupal\cas\Event\CasPreValidateServerConfigEvent;
use Drupal\cas\Exception\CasValidateException;
use Drupal\cas\Model\CasProtocolVersion;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LogLevel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The CAS ticket validator service.
 */
class CasValidator {

  public function __construct(
    protected readonly ClientInterface $httpClient,
    protected readonly CasHelper $casHelper,
    protected readonly UrlGeneratorInterface $urlGenerator,
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Validate the service ticket parameter present in the request.
   *
   * This method will return the username of the user if valid, and raise an
   * exception if the ticket is not found or not valid.
   *
   * @param string $ticket
   *   The CAS authentication ticket to validate.
   * @param array $service_params
   *   An array of query string parameters to add to the service URL.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws \Drupal\cas\Exception\CasValidateException
   *   Thrown if there was a problem making the validation request or
   *   if there was a local configuration issue.
   */
  public function validateTicket(string $ticket, array $service_params = []): CasPropertyBag {
    $casServerConfig = CasServerConfig::createFromModuleConfig($this->casHelper->getCasSettings());
    // Allow modules to modify the server config before it's used to validate
    // the login ticket.
    $event = new CasPreValidateServerConfigEvent($casServerConfig);
    $this->eventDispatcher->dispatch($event);
    $this->eventDispatcher->dispatch($event, 'cas.pre_validate_server_config');

    // Determine the path to send the validation request to on the CAS server.
    $path = '';
    switch ($casServerConfig->getProtocolVersion()) {
      case CasProtocolVersion::Version1:
        $path = 'validate';
        break;

      case CasProtocolVersion::Version2:
        if ($this->casHelper->getCasSetting('proxy.can_be_proxied')) {
          $path = 'proxyValidate';
        }
        else {
          $path = 'serviceValidate';
        }
        break;

      case CasProtocolVersion::Version3:
        if ($this->casHelper->getCasSetting('proxy.can_be_proxied')) {
          $path = 'p3/proxyValidate';
        }
        else {
          $path = 'p3/serviceValidate';
        }
        break;
    }

    $params = [];
    $params['service'] = $this->urlGenerator->generate('cas.service', $service_params, UrlGeneratorInterface::ABSOLUTE_URL);
    $params['ticket'] = $ticket;
    if ($this->casHelper->getCasSetting('proxy.initialize')) {
      $params['pgtUrl'] = $this->formatProxyCallbackUrl();
    }

    // Dispatch an event that allows modules to alter the validation path or
    // URL parameters.
    $pre_validate_event = new CasPreValidateEvent($path, $params);
    $this->eventDispatcher->dispatch($pre_validate_event);
    $this->eventDispatcher->dispatch($pre_validate_event, 'cas.pre_validate');
    $validate_url = $casServerConfig->getServerBaseUrl() . $pre_validate_event->getValidationPath();
    if (!empty($pre_validate_event->getParameters())) {
      $validate_url .= '?' . UrlHelper::buildQuery($pre_validate_event->getParameters());
    }

    $this->casHelper->log(
      LogLevel::DEBUG,
      'Attempting to validate service ticket %ticket by making request to URL %url',
      ['%ticket' => $ticket, '%url' => $validate_url]
    );

    try {
      $response = $this->httpClient->get($validate_url, $casServerConfig->getCasServerGuzzleConnectionOptions());
      $response_data = $response->getBody()->__toString();
      $this->casHelper->log(LogLevel::DEBUG, "Validation response received from CAS server: %data", ['%data' => $response_data]);
    }
    catch (TransferException $e) {
      throw new CasValidateException("Error with request to validate ticket: " . $e->getMessage());
    }

    $protocol_version = $casServerConfig->getProtocolVersion();
    $cas_property_bag = match($protocol_version) {
      CasProtocolVersion::Version1 => $this->validateVersion1($response_data),
      CasProtocolVersion::Version2, CasProtocolVersion::Version3 => $this->validateVersion2($response_data),
    };

    // Dispatch an event that allows modules to alter the CAS property bag.
    $event = new CasPostValidateEvent($response_data, $cas_property_bag);
    $this->eventDispatcher->dispatch($event);
    $this->eventDispatcher->dispatch($event, 'cas.post_validate');
    return $event->getCasPropertyBag();
  }

  /**
   * Validation of a service ticket for Version 1 of the CAS protocol.
   *
   * @param string $data
   *   The raw validation response data from CAS server.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws \Drupal\cas\Exception\CasValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validateVersion1(string $data): CasPropertyBag {
    if (preg_match('/^no\n/', $data)) {
      throw new CasValidateException("Ticket did not pass validation.");
    }
    elseif (!preg_match('/^yes\n/', $data)) {
      throw new CasValidateException("Malformed response from CAS server.");
    }

    // Ticket is valid, need to extract the username.
    $arr = preg_split('/\n/', $data);
    $user = trim($arr[1]);
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Extracted username %user from validation response data.",
      ['%user' => $user]
    );
    return new CasPropertyBag($user);
  }

  /**
   * Validation of a service ticket for Version 2 of the CAS protocol.
   *
   * @param string $data
   *   The raw validation response data from CAS server.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   Contains user info from the CAS server.
   *
   * @throws \Drupal\cas\Exception\CasValidateException
   *   Thrown if there was a problem parsing the validation data.
   */
  private function validateVersion2(string $data): CasPropertyBag {
    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";

    // Suppress errors from this function, as we intend to throw our own
    // exception.
    if (@$dom->loadXML($data) === FALSE) {
      throw new CasValidateException("XML from CAS server is not valid.");
    }

    $failure_elements = $dom->getElementsByTagName('authenticationFailure');
    if ($failure_elements->length > 0) {
      // Failed validation, extract the message and throw exception.
      $failure_element = $failure_elements->item(0);
      $error_code = $failure_element->getAttribute('code');
      $error_msg = $failure_element->nodeValue;
      throw new CasValidateException("Error Code " . trim($error_code) . ": " . trim($error_msg));
    }

    $success_elements = $dom->getElementsByTagName("authenticationSuccess");
    if ($success_elements->length === 0) {
      // All responses should have either an authenticationFailure
      // or authenticationSuccess node.
      throw new CasValidateException("XML from CAS server is not valid.");
    }

    // There should only be one success element, grab it and extract username.
    $success_element = $success_elements->item(0);
    $user_element = $success_element->getElementsByTagName("user");
    if ($user_element->length == 0) {
      throw new CasValidateException("No user found in ticket validation response.");
    }
    $username = $user_element->item(0)->nodeValue;
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Extracted username %user from validation response.",
      ['%user' => $username]
    );
    $property_bag = new CasPropertyBag($username);

    // If the server provided any attributes, parse them out into the property
    // bag.
    $attribute_elements = $dom->getElementsByTagName("attributes");
    if ($attribute_elements->length > 0) {
      $property_bag->setAttributes($this->parseAttributes($attribute_elements));
    }

    // Look for a proxy chain, and if it exists, validate it against config.
    $proxy_chain = $success_element->getElementsByTagName("proxy");
    if ($this->casHelper->getCasSetting('proxy.can_be_proxied') && $proxy_chain->length > 0) {
      $this->verifyProxyChain($proxy_chain);
    }

    if ($this->casHelper->getCasSetting('proxy.initialize')) {
      // Extract the PGTIOU from the XML.
      $pgt_element = $success_element->getElementsByTagName("proxyGrantingTicket");
      if ($pgt_element->length == 0) {
        throw new CasValidateException("Proxy initialized, but no PGTIOU provided in response.");
      }
      $pgt = $pgt_element->item(0)->nodeValue;
      $this->casHelper->log(
        LogLevel::DEBUG,
        "Extracted PGT %pgt from validation response.",
        ['%pgt' => $pgt]
      );
      $property_bag->setPgt($pgt);
    }
    return $property_bag;
  }

  /**
   * Verify a proxy chain from the CAS Server.
   *
   * Proxy chains from CAS Server responses are compared against the config
   * to ensure only allowed proxy chains are validated.
   *
   * @param \DOMNodeList $proxy_chain
   *   An XML element containing proxy values, from most recent to first.
   *
   * @throws \Drupal\cas\Exception\CasValidateException
   *   Thrown if the proxy chain did not match the allowed list from settings.
   */
  private function verifyProxyChain(\DOMNodeList $proxy_chain): void {
    $allowed_proxy_chains_raw = $this->casHelper->getCasSetting('proxy.proxy_chains');
    $allowed_proxy_chains = $this->parseAllowedProxyChains($allowed_proxy_chains_raw);
    $server_chain = $this->parseServerProxyChain($proxy_chain);
    $this->casHelper->log(LogLevel::DEBUG, "Attempting to verify supplied proxy chain: %chain", ['%chain' => print_r($server_chain, TRUE)]);

    // Loop through the allowed chains, checking the supplied chain for match.
    foreach ($allowed_proxy_chains as $chain) {
      // If the lengths mismatch, cannot be a match.
      if (count($chain) != count($server_chain)) {
        continue;
      }

      // Loop through regex in the chain, matching against supplied URL.
      $flag = TRUE;
      foreach ($chain as $index => $regex) {
        if (preg_match('/^\/.*\/[ixASUXu]*$/s', $regex)) {
          if (!(preg_match($regex, $server_chain[$index]))) {
            $flag = FALSE;
            $this->casHelper->log(
              LogLevel::DEBUG,
              "Failed to match %regex with supplied %chain",
              ['%regex' => $regex, '%chain' => $server_chain[$index]]
            );
            break;
          }
        }
        else {
          if (!(strncasecmp($regex, $server_chain[$index], strlen($regex)) == 0)) {
            $flag = FALSE;
            $this->casHelper->log(
              LogLevel::DEBUG,
              "Failed to match %regex with supplied %chain",
              ['%regex' => $regex, '%chain' => $server_chain[$index]]
            );
            break;
          }
        }
      }

      // If we have a match, return.
      if ($flag == TRUE) {
        $this->casHelper->log(
          LogLevel::DEBUG,
          "Matched allowed chain: %chain",
          ['%chain' => print_r($chain, TRUE)]
        );
        return;
      }
    }

    // If we've reached this point, no chain was validated, so throw exception.
    throw new CasValidateException("Proxy chain did not match allowed list.");
  }

  /**
   * Parse the proxy chain config into a usable data structure.
   *
   * @param string $proxy_chains
   *   A newline-delimited list of allowed proxy chains.
   *
   * @return array
   *   An array of allowed proxy chains, each containing an array of regular
   *   expressions for a URL in the chain.
   */
  private function parseAllowedProxyChains(string $proxy_chains): array {
    $chain_list = [];

    // Split configuration string on vertical whitespace.
    $chains = preg_split('/\v/', $proxy_chains, -1, PREG_SPLIT_NO_EMPTY);

    // Loop through chains, splitting out each URL.
    foreach ($chains as $chain) {
      // Split chain string on any whitespace character.
      $list = preg_split('/\s/', $chain, -1, PREG_SPLIT_NO_EMPTY);

      $chain_list[] = $list;
    }
    return $chain_list;
  }

  /**
   * Parse the XML proxy list from the CAS Server.
   *
   * @param \DOMNodeList $xml_list
   *   An XML element containing proxy values, from most recent to first.
   *
   * @return array
   *   An array of proxy values, from most recent to first.
   */
  private function parseServerProxyChain(\DOMNodeList $xml_list): array {
    $proxies = [];
    // Loop through the DOMNodeList, adding each proxy to the list.
    foreach ($xml_list as $node) {
      $proxies[] = $node->nodeValue;
    }
    return $proxies;
  }

  /**
   * Parse the attributes list from the CAS Server into an array.
   *
   * @param \DOMNodeList $xml_list
   *   An XML element containing attributes.
   *
   * @return array
   *   An associative array of attributes.
   */
  private function parseAttributes(\DOMNodeList $xml_list): array {
    $attributes = [];
    $node = $xml_list->item(0);
    foreach ($node->childNodes as $child) {
      $name = $child->localName;
      $value = $child->nodeValue;
      $attributes[$name][] = $value;
    }
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Parsed the following attributes from the validation response: %attributes",
      ['%attributes' => print_r($attributes, TRUE)]
    );
    return $attributes;
  }

  /**
   * Format the pgtCallbackURL parameter for use with proxying.
   *
   * We have to do a str_replace to force https for the proxy callback URL,
   * because it must use https, and setting the option 'https => TRUE' in the
   * options array won't force https if the user accessed the login route over
   * http and mixed-mode sessions aren't allowed.
   *
   * @return string
   *   The pgtCallbackURL, fully formatted.
   */
  private function formatProxyCallbackUrl(): string {
    return str_replace('http://', 'https://', $this->urlGenerator->generateFromRoute('cas.proxyCallback', [], [
      'absolute' => TRUE,
    ]));
  }

}
