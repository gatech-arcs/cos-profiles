<?php

declare(strict_types=1);

namespace Drupal\cas\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cas\Service\CasUserManager;

/**
 * Defines an access checker that restricts CAS users for certain routes.
 */
class CasUserAccessCheck implements AccessInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly CasUserManager $casUserManager,
  ) {}

  /**
   * Checks the access to routes tagged with '_cas_user_access'.
   *
   * If the current user account is linked to a CAS account and the setting
   * 'restrict_password_management' is TRUE, deny the access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $settings = $this->configFactory->get('cas.settings');
    if (
      // The 'user_accounts.restrict_password_management' is FALSE.
      !$settings->get('user_accounts.restrict_password_management')
      // Or the route is accessed by an anonymous users.
      || $account->isAnonymous()
      // Or the user doesn't have a linked CAS account.
      || !$this->casUserManager->getCasUsernameForAccount((int) $account->id())
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden("Is logged in CAS user and 'restrict_password_management' is TRUE");
  }

}
