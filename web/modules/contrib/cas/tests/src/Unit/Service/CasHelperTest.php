<?php

declare(strict_types=1);

namespace Drupal\Tests\cas\Unit\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Utility\Token;
use Drupal\Tests\UnitTestCase;
use Drupal\cas\Service\CasHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LogLevel;

/**
 * CasHelper unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Service\CasHelper
 */
class CasHelperTest extends UnitTestCase {

  use ProphecyTrait;
  /**
   * The mocked Url generator.
   */
  protected UrlGeneratorInterface|MockObject $urlGenerator;

  /**
   * The mocked logger factory.
   */
  protected LoggerChannelFactory|MockObject $loggerFactory;

  /**
   * The mocked log channel.
   */
  protected LoggerChannel|MockObject $loggerChannel;

  /**
   * The token service.
   */
  protected ObjectProphecy $token;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerFactory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactory');
    $this->loggerChannel = $this->createMock('\Drupal\Core\Logger\LoggerChannel');
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('cas')
      ->willReturn($this->loggerChannel);
    $this->token = $this->prophesize(Token::class);
    $this->token->replace('Use <a href="[cas:login-url]">CAS login</a>')
      ->willReturn('Use <a href="/caslogin">CAS login</a>');
    $this->token->replace('<script>alert("Hacked!");</script>')
      ->willReturn('<script>alert("Hacked!");</script>');
  }

  /**
   * Test the logging capability.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOn(): void {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => TRUE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should be called twice.
    $this->loggerChannel->expects($this->exactly(2))
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * Test our log wrapper when debug logging is off.
   *
   * @covers ::log
   * @covers ::__construct
   */
  public function testLogWhenDebugTurnedOff(): void {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'advanced.debug_log' => FALSE,
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    // The actual logger should only called once, when we log an error.
    $this->loggerChannel->expects($this->once())
      ->method('log');

    $cas_helper->log(LogLevel::DEBUG, 'This is a debug log');
    $cas_helper->log(LogLevel::ERROR, 'This is an error log');
  }

  /**
   * Tests the message generator.
   *
   * @covers ::getMessage
   */
  public function testGetMessage(): void {
    /** @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'arbitrary_message' => 'Use <a href="[cas:login-url]">CAS login</a>',
        'messages' => [
          'empty_message' => '',
          'do_not_trust_user_input' => '<script>alert("Hacked!");</script>',
        ],
      ],
    ]);
    $cas_helper = new CasHelper($config_factory, $this->loggerFactory, $this->token->reveal());

    $message = $cas_helper->getMessage('arbitrary_message');
    $this->assertInstanceOf(FormattableMarkup::class, $message);
    $this->assertEquals('Use <a href="/caslogin">CAS login</a>', $message);

    // Empty message.
    $message = $cas_helper->getMessage('messages.empty_message');
    $this->assertSame('', $message);

    // Check hacker entered message.
    $message = $cas_helper->getMessage('messages.do_not_trust_user_input');
    // Check that the dangerous tags were stripped-out.
    $this->assertEquals('alert("Hacked!");', $message);
  }

}
