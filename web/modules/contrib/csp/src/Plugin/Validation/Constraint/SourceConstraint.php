<?php

namespace Drupal\csp\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Content Security Policy Source Constraint.
 *
 * Configurable constraints are
 *  - protocols (e.g. https:)
 *  - Domains/URIs (e.g. https://example.com)
 *  - Hashes (e.g. 'sha256-*')
 */
#[Constraint(
  id: 'CspSource',
  label: new TranslatableMarkup('CSP Source', [], ['context' => 'Validation']),
  type: ['string'],
)]
class SourceConstraint extends SymfonyConstraint {

  /**
   * Whether nonces are valid.
   *
   * @var bool
   */
  public bool $allowNonce = FALSE;

  /**
   * Whether hashes are valid.
   *
   * @var bool
   */
  public bool $allowHash = TRUE;

}
