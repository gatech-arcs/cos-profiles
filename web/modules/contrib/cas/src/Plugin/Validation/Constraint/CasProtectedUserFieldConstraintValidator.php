<?php

declare(strict_types=1);

namespace Drupal\cas\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\cas\Service\CasUserManager;
use Drupal\user\Plugin\Validation\Constraint\ProtectedUserFieldConstraintValidator;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Decorates the ProtectedUserFieldConstraint constraint.
 */
class CasProtectedUserFieldConstraintValidator extends ProtectedUserFieldConstraintValidator {

  public function __construct(
    UserStorageInterface $userStorage,
    AccountProxyInterface $currenUser,
    protected readonly CasUserManager $casUserManager,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($userStorage, $currenUser);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('current_user'),
      $container->get('cas.user_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    // Skip the validator if CAS is configured with restricted password
    // management and if the user being validated is a CAS user.
    if (!empty($items)) {
      $account = $items->getEntity();
      $restrictedPasswordManagement = $this->configFactory->get('cas.settings')->get('user_accounts.restrict_password_management');
      if ($account->id() !== NULL && $restrictedPasswordManagement && !empty($this->casUserManager->getCasUsernameForAccount((int) $account->id()))) {
        return;
      }
    }

    parent::validate($items, $constraint);
  }

}
