<?php

namespace Siaoynli\PhoneAuth\Services;

use Siaoynli\PhoneAuth\Models\PhoneVerificationCode;

class PhoneAuthService
{
  protected $config;
  protected $smsGateway;

  public function __construct()
  {
    $this->config = config('plugins.siaoynli-phone-auth-plugin', []);
    $this->initSmsGateway();
  }

  /**
   * 初始化短信网关
   */
  protected function initSmsGateway(): void
  {
    $driver = $this->config['sms']['driver'] ?? 'log';

    // 这里可以注册不同的短信驱动
    switch ($driver) {
      case 'aliyun':
        $this->smsGateway = new \Siaoynli\PhoneAuth\Drivers\AliyunSmsDriver($this->config);
        break;
      case 'dxsms':
        $this->smsGateway = new \Siaoynli\PhoneAuth\Drivers\DxSmsDriver($this->config);
        break;
      default:
        $this->smsGateway = new \Siaoynli\PhoneAuth\Drivers\LogSmsDriver($this->config);
    }
  }

  /**
   * 发送验证码
   */
  public function sendCode(string $phone): array
  {
    // 验证手机号
    if (!$this->validatePhone($phone)) {
      return ['success' => false, 'message' => '手机号格式不正确', 'code' => 400];
    }

    // 检查冷却时间
    $lastCode = PhoneVerificationCode::where('phone', $phone)
      ->latest()
      ->first();

    if ($lastCode && $lastCode->created_at->addSeconds(
      $this->config['code']['resend_cooldown'] ?? 60
    ) > now()) {
      return [
        'success' => false,
        'message' => '请求太频繁，请稍候再试!',
        'code' => 429,
        'retry_after' => $lastCode->created_at->addSeconds(
          $this->config['code']['resend_cooldown']
        )->diffInSeconds(now()),
      ];
    }

    // 生成验证码
    $code = $this->generateCode();

    // 保存到数据库
    PhoneVerificationCode::create([
      'phone' => $phone,
      'code' => $code,
      'attempts' => 0,
      'expires_at' => now()->addMinutes($this->config['code']['expire']),
    ]);

    // 发送短信
    try {
      $this->smsGateway->send($phone, $code);
      return ['status' => 'success', 'message' => '验证码已发送'];
    } catch (\Exception $e) {
      \Log::error('发送短信失败: ' . $e->getMessage());
      return ['status' => 'fail', 'message' => '发送验证码失败:' . $e->getMessage(), 'code' => 500];
    }
  }

  /**
   * 验证码登录
   */
  public function login(string $phone, string $code): array
  {
    // 验证手机号
    if (!$this->validatePhone($phone)) {
      return ['status' => 'fail', 'message' => '手机号格式不正确', 'code' => 400];
    }

    // 获取验证码记录
    $record = PhoneVerificationCode::where('phone', $phone)
      ->where('expires_at', '>', now())
      ->latest()
      ->first();

    if (!$record) {
      return ['status' => 'fail', 'message' => '验证码已过期，请重新获取', 'code' => 400];
    }

    // 检查尝试次数
    if ($record->attempts >= $this->config['code']['attempts']) {
      $record->delete();
      return ['status' => 'fail', 'message' => '尝试次数过多，请重新获取验证码', 'code' => 429];
    }

    // 验证验证码
    if ($record->code !== $code) {
      $record->increment('attempts');
      return ['status' => 'fail', 'message' => '验证码错误', 'code' => 400];
    }

    // 删除已使用的验证码
    $record->delete();

    // 获取或创建用户
    $userModel = config('plugins.siaoynli-phone-auth-plugin.user.model', \App\Models\User::class);
    $phoneField = config('plugins.siaoynli-phone-auth-plugin.user.phone_field', 'phone');

    $user = $userModel::where($phoneField, $phone)->first();

    if (!$user && config('plugins.siaoynli-phone-auth-plugin.features.register')) {
      //todo 注册用户
    }

    if (!$user) {
      return ['status' => 'fail', 'message' => '用户不存在', 'code' => 404];
    }

    // 生成 API Token
    $token = $user->createToken('phone-auth', ['*'])->plainTextToken;

    return [
      'status' => 'success',
      'message' => '登录成功',
      'data' => [
        'token' => $token,
        'user' => $user,
      ],
    ];
  }

  /**
   * 验证手机号格式
   */
  protected function validatePhone(string $phone): bool
  {
    return preg_match('/^1[3-9]\d{9}$/', $phone);
  }

  /**
   * 生成验证码
   */
  protected function generateCode(): string
  {
    $length = $this->config['code']['length'] ?? 6;
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
  }
}
