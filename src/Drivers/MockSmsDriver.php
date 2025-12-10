<?php

namespace Siaoynli\PhoneAuth\Drivers;

use Siaoynli\PhoneAuth\Contracts\SmsGateway;

/**
 * 模拟驱动 - 用于单元测试
 */
class MockSmsDriver implements SmsGateway
{
  protected $config;
  protected $sentMessages = [];

  public function __construct(array $config)
  {
    $this->config = $config;
  }

  /**
   * 发送短信（模拟）
   */
  public function send(string $phone, string $code): bool
  {
    $this->sentMessages[] = [
      'phone' => $phone,
      'code' => $code,
      'sent_at' => now(),
    ];

    return true;
  }

  /**
   * 获取已发送的消息
   */
  public function getSentMessages(): array
  {
    return $this->sentMessages;
  }

  /**
   * 清空已发送的消息
   */
  public function clearSentMessages(): void
  {
    $this->sentMessages = [];
  }

  public function getDriver(): string
  {
    return 'mock';
  }
}
