<?php

namespace Siaoynli\PhoneAuth\Tests\Unit;

use Tests\TestCase;
use Siaoynli\PhoneAuth\Models\PhoneVerificationCode;
use Siaoynli\PhoneAuth\Services\PhoneAuthService;

class PhoneAuthServiceTest extends TestCase
{
  protected $service;

  protected function setUp(): void
  {
    parent::setUp();
    $this->service = app(PhoneAuthService::class);
    PhoneVerificationCode::truncate();
  }

  /**
   * 测试验证手机号格式
   */
  public function test_validate_phone_format(): void
  {
    // 测试有效的手机号
    $result = $this->service->sendCode('13800000000');
    $this->assertTrue($result['success']);

    // 测试无效的手机号
    $result = $this->service->sendCode('12345678901');
    $this->assertFalse($result['success']);
  }

  /**
   * 测试验证码生成
   */
  public function test_verification_code_generation(): void
  {
    $this->service->sendCode('13800000000');

    $code = PhoneVerificationCode::where('phone', '13800000000')
      ->latest()
      ->first()
      ->code;

    // 验证码长度应该为 6
    $this->assertEquals(6, strlen($code));

    // 验证码应该只包含数字
    $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
  }
}
