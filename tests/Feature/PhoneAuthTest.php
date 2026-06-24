<?php

namespace Siaoynli\PhoneAuth\Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Siaoynli\PhoneAuth\Models\PhoneAuthLog;
use Siaoynli\PhoneAuth\Models\PhoneVerificationCode;

class PhoneAuthTest extends TestCase
{
    /**
     * 路由前缀 — 与 config/plugin.php 中的 route_prefix 保持一致
     */
    protected string $prefix = '/api/plugin/phone-auth';

    protected function setUp(): void
    {
        parent::setUp();
        PhoneVerificationCode::truncate();
        PhoneAuthLog::truncate();
    }

    /**
     * 测试发送验证码
     */
    public function test_can_send_verification_code(): void
    {
        $response = $this->getJson("{$this->prefix}/send-code?phone=13800000000");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => '验证码已发送',
        ]);

        $this->assertDatabaseHas('phone_verification_codes', [
            'phone' => '13800000000',
        ]);
    }

    /**
     * 测试手机号格式验证
     */
    public function test_invalid_phone_format(): void
    {
        $response = $this->getJson("{$this->prefix}/send-code?phone=invalid-phone");

        $response->assertStatus(422);
    }

    /**
     * 测试频繁发送验证码被拒绝
     */
    public function test_cannot_send_code_too_frequently(): void
    {
        $phone = '13800000000';

        // 第一次发送
        $response1 = $this->getJson("{$this->prefix}/send-code?phone={$phone}");
        $response1->assertSuccessful();

        // 立即再次发送
        $response2 = $this->getJson("{$this->prefix}/send-code?phone={$phone}");

        $response2->assertStatus(429);
        $response2->assertJsonStructure(['retry_after']);
    }

    /**
     * 测试验证码登录
     */
    public function test_can_login_with_verification_code(): void
    {
        $phone = '13800000000';

        // 发送验证码
        $this->getJson("{$this->prefix}/send-code?phone={$phone}");

        // 获取发送的验证码
        $code = PhoneVerificationCode::where('phone', $phone)
            ->latest()
            ->first()
            ->code;

        // 使用验证码登录
        $response = $this->postJson("{$this->prefix}/login", [
            'phone' => $phone,
            'code'  => $code,
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => '登录成功',
        ]);
        $response->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'phone',
                ],
            ],
        ]);

        // 验证用户已创建（自动注册）
        $this->assertDatabaseHas('users', ['phone' => $phone]);
    }

    /**
     * 测试错误的验证码
     */
    public function test_cannot_login_with_wrong_code(): void
    {
        $phone = '13800000000';

        // 发送验证码
        $this->getJson("{$this->prefix}/send-code?phone={$phone}");

        // 使用错误的验证码登录
        $response = $this->postJson("{$this->prefix}/login", [
            'phone' => $phone,
            'code'  => '000000',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => '验证码错误',
        ]);
    }

    /**
     * 测试验证码过期
     */
    public function test_cannot_login_with_expired_code(): void
    {
        $phone = '13800000000';

        // 创建一个已过期的验证码
        PhoneVerificationCode::create([
            'phone'      => $phone,
            'code'       => '123456',
            'attempts'   => 0,
            'expires_at' => now()->subMinutes(10),
        ]);

        // 尝试使用已过期的验证码
        $response = $this->postJson("{$this->prefix}/login", [
            'phone' => $phone,
            'code'  => '123456',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => '验证码已过期，请重新获取',
        ]);
    }

    /**
     * 测试登出
     */
    public function test_can_logout(): void
    {
        $user = User::factory()->create(['phone' => '13800000000']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->prefix}/logout");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'message' => '退出成功',
        ]);
    }

    /**
     * 测试获取用户信息
     */
    public function test_can_get_user_profile(): void
    {
        $user = User::factory()->create(['phone' => '13800000000']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("{$this->prefix}/profile");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('data.id', $user->id);
        $response->assertJsonPath('data.phone', '13800000000');
    }

    /**
     * 测试尝试次数超限
     */
    public function test_cannot_login_after_max_attempts(): void
    {
        $phone = '13800000000';

        // 发送验证码
        $this->getJson("{$this->prefix}/send-code?phone={$phone}");

        // 尝试 5 次（达到限制）
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("{$this->prefix}/login", [
                'phone' => $phone,
                'code'  => 'wrong-code',
            ]);
        }

        // 第 6 次应该被拒绝
        $response = $this->postJson("{$this->prefix}/login", [
            'phone' => $phone,
            'code'  => 'wrong-code',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'message' => '尝试次数过多，请重新获取验证码',
        ]);
    }
}
