<?php

namespace Siaoynli\PhoneAuth\Drivers;

use Siaoynli\PhoneAuth\Contracts\SmsGateway;

/**
 * 日志驱动 - 用于本地开发和测试
 * 将验证码记录到日志中，不真实发送短信
 */
class LogSmsDriver implements SmsGateway
{
  protected $config;

  public function __construct(array $config)
  {
    $this->config = $config;
  }

  /**
   * 发送短信（记录到日志）
   */
  public function send(string $phone, string $code): bool
  {
    \Log::info('Phone Auth SMS', [
      'phone' => $phone,
      'code' => $code,
      'timestamp' => now(),
    ]);

    // 在本地开发环境下，也可以保存到文件方便查看
    if (app()->environment('local')) {
      $logFile = storage_path('logs/phone-auth-codes.log');
      $message = sprintf(
        "[%s] Phone: %s, Code: %s\n",
        now()->format('Y-m-d H:i:s'),
        $phone,
        $code
      );
      file_put_contents($logFile, $message, FILE_APPEND);
    }

    return true;
  }

  public function getDriver(): string
  {
    return 'log';
  }
}
