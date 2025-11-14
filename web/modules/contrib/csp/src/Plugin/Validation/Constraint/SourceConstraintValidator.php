<?php

namespace Drupal\csp\Plugin\Validation\Constraint;

use Drupal\csp\Csp;
use Symfony\Component\Validator\Constraint as ConstraintAlias;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a string is a valid Content Security Policy Source.
 */
class SourceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, ConstraintAlias $constraint): void {
    if (!$constraint instanceof SourceConstraint) {
      throw new UnexpectedTypeException($constraint, SourceConstraint::class);
    }

    if (!$constraint->allowNonce && self::isValidNonce($value)) {
      $this->context->buildViolation("Nonce sources are not valid.")
        ->setInvalidValue($value)
        ->setCause('nonce')
        ->addViolation();
    }
    elseif (!$constraint->allowHash && self::isValidHash($value)) {
      $this->context->buildViolation("Hash sources are not valid.")
        ->setInvalidValue($value)
        ->setCause('hash')
        ->addViolation();
    }
    elseif (
      !self::isValidProtocol($value)
      && !self::isValidHost($value)
      && !($constraint->allowHash && self::isValidHash($value))
      && !($constraint->allowNonce && self::isValidNonce($value))
    ) {
      $this->context->buildViolation('"%value" is not a valid source')
        ->setParameter('%value', $value)
        ->setInvalidValue($value)
        ->addViolation();
    }
  }

  /**
   * Verifies if a value is a valid protocol.
   *
   * @param string $value
   *   The value to verify.
   *
   * @return bool
   *   TRUE if the value is a protocol.
   */
  protected static function isValidProtocol(string $value): bool {
    return preg_match('<^([a-z][a-z0-9\-.+]*:)$>', $value);
  }

  /**
   * Verifies the syntax of the given URL.
   *
   * Similar to UrlHelper::isValid(), except:
   * - protocol is optional.
   * - domains must have at least a top-level and secondary domain.
   * - an initial subdomain wildcard is allowed
   * - wildcard is allowed as port value
   * - query is not allowed.
   *
   * @param string $value
   *   The value to verify.
   *
   * @return bool
   *   TRUE if the URL is in a valid format, FALSE otherwise.
   */
  protected static function isValidHost(string $value): bool {
    return (bool) preg_match("
        /^                                                      # Start at the beginning of the text
        (?:[a-z][a-z0-9\-.+]*:\/\/)?                             # Scheme (optional)
        (?:
          (?:                                                   # A domain name or a IPv4 address
            (?:\*\.)?                                           # Wildcard prefix (optional)
            (?:(?:[a-z0-9\-\.]|%[0-9a-f]{2})+\.)+
            (?:[a-z0-9\-\.]|%[0-9a-f]{2})+
          )
          |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
          |localhost
        )
        (?::(?:[0-9]+|\*))?                                     # Server port number or wildcard (optional)
        (?:[\/|\?]
          (?:[\w#!:\.\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})     # The path (optional)
        *)?
      $/xi", $value);
  }

  /**
   * Verifies if a value is a valid hash.
   *
   * @param string $value
   *   The value to verify.
   *
   * @return bool
   *   TRUE if the value is a properly formatted hash source.
   */
  protected static function isValidHash(string $value): bool {
    // '{hashAlgorithm}-{base64-value}'
    $hashAlgoMatch = '(' . implode('|', Csp::HASH_ALGORITHMS) . ')';

    return preg_match("<^'" . $hashAlgoMatch . "-[\w+/_-]+=*'$>", $value);
  }

  /**
   * Verifies if a value is a valid nonce.
   *
   * @param string $value
   *   The value to verify.
   *
   * @return bool
   *   TRUE if the value is a properly formatted nonce source.
   */
  protected static function isValidNonce(string $value): bool {
    return preg_match("<^'nonce-[\w+/_-]+=*'$>", $value);
  }

}
