<?php

declare(strict_types=1);

namespace Drupal\cas\Drush\Commands;

use Drupal\cas\Service\CasUserManager;
use Drush\Attributes\Argument;
use Drush\Attributes\Command;
use Drush\Attributes\Help;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush command file for CAS commands.
 */
class CasCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    #[Autowire(service: 'cas.user_manager')]
    protected readonly CasUserManager $casUserManager,
  ) {
    parent::__construct();
  }

  /**
   * Sets a CAS username for an existing Drupal user.
   */
  #[Command(name: 'cas:set-cas-username')]
  #[Argument(name: 'drupalUsername', description: 'The Drupal username of the user to modify.')]
  #[Argument(name: 'casUsername', description: 'The CAS username to assign to the user.')]
  #[Help(description: 'Set a CAS username for an existing Drupal user.')]
  public function setCasUsername(string $drupalUsername, string $casUsername): void {
    $account = user_load_by_name($drupalUsername);
    if ($account) {
      $this->casUserManager->setCasUsernameForAccount($account, $casUsername);
      $this->logger->success(dt('Assigned CAS username "!casUsername" to user "!drupalUsername"', [
        '!casUsername' => $casUsername,
        '!drupalUsername' => $drupalUsername,
      ]));
    }
    else {
      $this->logger->error(dt('Unable to load user: !user', ['!user' => $drupalUsername]));
    }
  }

}
