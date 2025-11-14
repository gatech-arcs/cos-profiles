<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Functional;

use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests the redirect to CAS after logout.
 *
 * @see \Drupal\Tests\cas\Unit\Routing\CasRouteEnhancerTest
 * @see \Drupal\cas\Routing\CasRouteEnhancer
 * @see \cas_form_user_logout_confirm_alter()
 *
 * @group cas
 */
class CasLogoutTest extends CasBrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'page_cache',
    'dynamic_page_cache',
    'block',
    'user',
    'cas_mock_server',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->placeBlock('system_menu_block:account');
  }

  /**
   * Tests the redirect to CAS logout page after Drupal user logout.
   *
   * @param 'link'|'form' $logout_method
   *   The method to use for logout.
   *
   * @testWith ["link"]
   *           ["form"]
   */
  public function testCasLogout(string $logout_method): void {
    // Create a local user.
    $account = $this->createUser([], 'beavis');
    // Create a CAS user.
    $this->createCasUser('beavis', 'beavis@example.com', 'needtp', [
      'firstname' => 'Beavis',
      'lastname' => 'El Gran Cornholio',
    ], $account);

    $do_drupal_logout = match ($logout_method) {
      'link' => function (): void {
        $this->getSession()->getPage()->clickLink('Log out');
        $this->loggedInUser = FALSE;
      },
      'form' => function (): void {
        $this->drupalGet('/user/logout');
        $this->assertSame($this->baseUrl . '/user/logout/confirm', $this->getUrl());
        $this->assertSession()->buttonExists('Log out')->press();
        $this->loggedInUser = FALSE;
      },
    };

    // With default settings, any user is redirected to the front page on
    // logout (Drupal core behavior).
    $this->drupalLogin($account);
    $do_drupal_logout();
    $this->assertSame($this->baseUrl . '/', $this->getUrl());
    $this->casLogin('beavis@example.com', 'needtp');
    $do_drupal_logout();
    $this->assertSame($this->baseUrl . '/', $this->getUrl());

    // Enable the setting.
    $this->config('cas.settings')
      ->set('logout.cas_logout', TRUE)
      ->save();

    // For a regular Drupal user session without CAS, the user is redirected to
    // the front page on logout.
    $this->drupalLogin($account);
    $do_drupal_logout();
    $this->assertSame($this->baseUrl . '/', $this->getUrl());

    // For a CAS user session, the user is redirected to a CAS logout url on
    // logout.
    $this->casLogin('beavis@example.com', 'needtp');
    $do_drupal_logout();
    // Assert the CAS destination url.
    // The page is going to be empty, because cas_mock_server does not currently
    // implement this functionality.
    $this->assertSame($this->baseUrl . '/cas-mock-server/logout', $this->getUrl());

    $this->config('cas.settings')
      ->set('logout.logout_destination', '<front>')
      ->save();

    // The logout destination is appended to the CAS url.
    $this->casLogin('beavis@example.com', 'needtp');
    $do_drupal_logout();
    $this->assertSame(
      $this->baseUrl . '/cas-mock-server/logout?service=' . str_replace('%2F', '/', rawurlencode($this->baseUrl . '/')),
      $this->getUrl(),
    );

    // Test with an external url as destination.
    $destination_url = 'https://example.com:12345/some/path?x=y#anchor';
    $this->config('cas.settings')
      ->set('logout.logout_destination', $destination_url)
      ->save();
    $this->casLogin('beavis@example.com', 'needtp');
    $do_drupal_logout();
    $this->assertSame(
      $this->baseUrl . '/cas-mock-server/logout?service=' . str_replace('%2F', '/', rawurlencode($destination_url)),
      $this->getUrl(),
    );

    // Test logout with an external cas url.
    // To do this, keep the cas mock server enabled for login, but then disable
    // it for logout.
    // Configure the CAS server to have a non-empty hostname.
    $this->config('cas.settings')
      ->set('server.hostname', 'cas.localhost')
      ->set('server.path', '/cas')
      ->save();
    $this->casLogin('beavis@example.com', 'needtp');
    /** @var \Drupal\cas_mock_server\ServerManager $cas_mock_server_manager */
    $cas_mock_server_manager = \Drupal::service('cas_mock_server.server_manager');
    $cas_mock_server_manager->stop();
    $this->disableRedirects();
    $do_drupal_logout();
    $this->assertSame(
      "https://cas.localhost/cas/logout?url=" . str_replace('%2F', '/', rawurlencode($destination_url)),
      $this->getSession()->getResponseHeader('Location'),
    );
  }

}
