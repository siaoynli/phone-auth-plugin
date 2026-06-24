<?php

namespace Siaoynli\PhoneAuth\Tests\Unit;

use Tests\TestCase;
use Siaoynli\PhoneAuth\Drivers\MockSmsDriver;
use Siaoynli\PhoneAuth\Models\PhoneVerificationCode;
use Siaoynli\PhoneAuth\Services\PhoneAuthService;

class PhoneAuthServiceTest extends TestCase
{
    protected PhoneAuthService $service;
    protected MockSmsDriver $smsGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsGateway = new MockSmsDriver([]);
        $this->service = new PhoneAuthService($this->smsGateway, [
            'code' => [
                'length'          => 6,
                'expire'          => 5,
                'attempts'        => 5,
                'resend_cooldown' => 60,
            ],
            'user' => [
                'model'       => \App\Models\User::class,
                'phone_field' => 'phone',
            ],
            'features' => [
                'register' => true,
                'bind'     => true,
                'logout'   => true,
            ],
        ]);

        PhoneVerificationCode::truncate();
    }

    /**
     * 测试验证手机号格式
     */
    public function test_validate_phone_format(): void
    {
        // 有效的手机号
        $this->assertTrue($this->service->validatePhone('13800000000'));
        $this->assertTrue($this->service->validatePhone('15912345678'));

        // 无效的手机号
        $this->assertFalse($this->service->validatePhone('12345678901'));
        $this->assertFalse($this->service->validatePhone('1380000'));
        $this->assertFalse($this->service->validatePhone('abc'));
    }

    /**
     * 测试验证码生成
     */
    public function test_verification_code_generation(): void
    {
        $result = $this->service->sendCode('13800000000');
        $this->assertTrue($result['success']);

        $record = PhoneVerificationCode::where('phone', '13800000000')
            ->latest()
            ->first();

        // 验证码长度应该为 6
        $this->assertEquals(6, strlen($record->code));

        // 验证码应该只包含数字
        $this->assertMatchesRegularExpression('/^\d{6}$/', $record->code);
    }

    /**
     * 测试 MockSmsDriver 正确接收短信
     */
    public function test_mock_driver_receives_sms(): void
    {
        $this->service->sendCode('13800000000');

        $messages = $this->smsGateway->getSentMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals('13800000000', $messages[0]['phone']);
        $this->assertNotEmpty($messages[0]['code']);
    }

    /**
     * 测试发送失败时返回统一格式
     */
    public function test_send_code_invalid_phone_returns_error(): void
    {
        $result = $this->service->sendCode('invalid');

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['code']);
    }

    /**
     * 测试登录成功返回 token
     */
    public function test_login_returns_token(): void
    {
        // 先发送验证码
        $this->service->sendCode('13800000000');

        $code = PhoneVerificationCode::where('phone', '13800000000')
            ->first()
            ->code;

        $result = $this->service->login('13800000000', $code);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('token', $result['data']);
    }
}
