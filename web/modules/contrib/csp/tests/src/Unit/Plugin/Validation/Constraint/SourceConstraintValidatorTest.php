<?php

namespace Drupal\Tests\csp\Unit\Plugin\Validation\Constraint;

use Drupal\Tests\UnitTestCase;
use Drupal\csp\Plugin\Validation\Constraint\SourceConstraint;
use Drupal\csp\Plugin\Validation\Constraint\SourceConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests for validating directive source values.
 */
class SourceConstraintValidatorTest extends UnitTestCase {

  /**
   * Source value test parameters.
   *
   * @return array<string, array{string, bool}>
   *   Array of test parameters.
   */
  private static function sourceProvider(
    bool $allowNonce = FALSE,
    bool $allowHash = TRUE,
  ): array {
    // cspell:disable
    return [
      'empty' => ['', FALSE],
      'tld' => ['com', FALSE],
      'wildcard domain' => ['*.com', FALSE],
      'bare domain ' => ['example.com', TRUE],
      'bare domain port' => ['example.com:1234', TRUE],
      'bare domain path' => ['example.com/baz', TRUE],
      'empty path' => ['example.com/', TRUE],
      'bare domain path query' => ['example.com/baz?foo=false', FALSE],
      'bare wild subdomain' => ['*.example.com', TRUE],
      'multiple wildcard subdomain' => ['*.*.example.com', FALSE],
      'inner wild subdomain' => ['foo.*.example.com', FALSE],
      'wild tld' => ['example.*', FALSE],

      'subdomain' => ['foo.example.com', TRUE],
      'subdomains' => ['foo.bar.example.com', TRUE],
      'subdomains path' => ['foo.bar.example.com/baz', TRUE],

      'http domain' => ['http://example.com', TRUE],
      'https domain' => ['https://example.com', TRUE],
      'ws' => ['ws://example.com', TRUE],
      'wss' => ['wss://example.com', TRUE],
      'https domain port' => ['https://example.com:1234', TRUE],
      'https domain port path' => ['https://example.com:1234/baz', TRUE],
      'https wild subdomain' => ['https://*.example.com', TRUE],

      'ipv4' => ['192.168.0.1', TRUE],
      'https ipv4' => ['https://192.168.0.1', TRUE],
      'https ipv4 path' => ['https://192.168.0.1/baz', TRUE],
      'https ipv4 port' => ['https://192.168.0.1:1234', TRUE],

      'ipv6' => ['[fd42:92f4:7eb8:c821:f685:9190:bf44:b2f5]', TRUE],
      'ipv6 short' => ['[fd42:92f4:7eb8:c821::b2f5]', TRUE],
      'https ipv6' => ['https://[fd42:92f4:7eb8:c821:f685:9190:bf44:b2f5]', TRUE],
      'https ipv6 short' => ['https://[fd42:92f4:7eb8:c821::b2f5]', TRUE],
      'https ipv6 port' => ['https://[fd42:92f4:7eb8:c821:f685:9190:bf44:b2f5]:1234', TRUE],
      'https ipv6 short port' => ['https://[fd42:92f4:7eb8:c821::b2f5]:1234', TRUE],
      'https ipv6 port path' => ['https://[fd42:92f4:7eb8:c821:f685:9190:bf44:b2f5]:1234/baz', TRUE],

      'localhost' => ['localhost', TRUE],
      'https localhost' => ['https://localhost', TRUE],
      'https localhost path' => ['https://localhost/baz', TRUE],
      'https localhost port' => ['https://localhost:1234', TRUE],
      'https localhost port path' => ['https://localhost:1234/baz', TRUE],

      'wild port' => ['example.com:*', TRUE],
      'wild subdomain wild port' => ['*.example.com:*', TRUE],
      'empty port' => ['https://example.com:', FALSE],
      'letter port' => ['example.com:b33f', FALSE],

      // @see https://www.w3.org/TR/CSP3/#grammardef-scheme-part
      // @see https://tools.ietf.org/html/rfc3986#section-3.1
      'other protocol' => ['example://localhost', TRUE],
      'edge case protocol' => ['example-foo.123+bar://localhost', TRUE],
      'protocol numeric first char' => ['1example://localhost', FALSE],
      // cspell:disable-next-line
      'protocol invalid symbol' => ['ex@mple://localhost', FALSE],
      'data' => ['data:', TRUE],
      'blob' => ['blob:', TRUE],
      'protocol missing colon' => ['http', FALSE],

      'valid hash ' => ["'sha256-m0zKW3SgFyV1D9aL5SVP9sTjV8ymQ9XirnpSfOsqCFk='", $allowHash],
      'invalid hash algorithm' => ["'sha404-m0zKW3SgFyV1D9aL5SVP9sTjV8ymQ9XirnpSfOsqCFk='", FALSE],
      'unquoted hash' => ["sha256-m0zKW3SgFyV1D9aL5SVP9sTjV8ymQ9XirnpSfOsqCFk=", FALSE],
      'nonce' => ["'nonce-onVJmd+hRq/20_YD-0zVBXOoOA='", $allowNonce],
      'unquoted nonce' => ["nonce-onVJmdhRq20YD0zVBXOoOA", FALSE],
      // Keywords are not allowed in configuration.
      'any' => ['*', FALSE],
      'self' => ["'self'", FALSE],
      'none' => ["'none'", FALSE],
    ];
    // cspell:enable
  }

  /**
   * Source value test parameters.
   *
   * @return array<string, array{string, bool}>
   *   Array of test parameters.
   */
  public static function disallowNonceSourceProvider(): array {
    return self::sourceProvider();
  }

  /**
   * Source value test parameters.
   *
   * @return array<string, array{string, bool}>
   *   Array of test parameters.
   */
  public static function allowNonceSourceProvider(): array {
    return self::sourceProvider(allowNonce: TRUE);
  }

  /**
   * Source value test parameters.
   *
   * @return array<string, array{string, bool}>
   *   Array of test parameters.
   */
  public static function disallowHashSourceProvider(): array {
    return self::sourceProvider(allowHash: FALSE);
  }

  /**
   * Test validating source values.
   */
  private function testValidate(
    string $source,
    bool $valid,
    bool $allowNonce = FALSE,
    bool $allowHash = TRUE,
  ): void {
    $validator = new SourceConstraintValidator();

    $context = $this->createMock(ExecutionContextInterface::class);
    $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $context->method('buildViolation')->willReturn($violationBuilder);
    $violationBuilder->method('setInvalidValue')->willReturnSelf();
    $violationBuilder->method('addViolation')->willReturnSelf();

    $validator->initialize($context);

    if ($valid) {
      $context->expects($this->never())
        ->method('buildViolation');
    }
    else {
      $context->expects($this->once())
        ->method('buildViolation');
    }

    $constraint = new SourceConstraint([
      'allowNonce' => $allowNonce,
      'allowHash' => $allowHash,
    ]);
    $validator->validate($source, $constraint);
  }

  /**
   * Nonce not permitted.
   *
   * @dataProvider disallowNonceSourceProvider
   */
  public function testValidateDisallowNonce(string $source, bool $valid): void {
    $this->testValidate($source, $valid);
  }

  /**
   * Nonce permitted.
   *
   * @dataProvider allowNonceSourceProvider
   */
  public function testValidateAllowNonce(string $source, bool $valid): void {
    $this->testValidate($source, $valid, allowNonce: TRUE);
  }

  /**
   * Hash not permitted.
   *
   * @dataProvider disallowHashSourceProvider
   */
  public function testValidateDisallowHash(string $source, bool $valid): void {
    $this->testValidate($source, $valid, allowHash: FALSE);
  }

}
