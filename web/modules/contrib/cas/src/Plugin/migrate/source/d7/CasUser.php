<?php

declare(strict_types=1);

namespace Drupal\cas\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal authmap source from database.
 *
 * @MigrateSource(
 *   id = "d7_cas_user",
 *   source_module = "cas"
 * )
 */
class CasUser extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('cas_user', 'c')->fields('c');
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'uid' => $this->t('The user identifier.'),
      'cas_name' => $this->t('Unique authentication name.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'uid' => [
        'type' => 'integer',
      ],
    ];
  }

}
