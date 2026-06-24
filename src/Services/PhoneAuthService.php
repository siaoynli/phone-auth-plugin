<?php

namespace Siaoynli\PhoneAuth\Services;

use Illuminate\Support\Facades\Log;
use Siaoynli\PhoneAuth\Contracts\SmsGateway;
use Siaoynli\PhoneAuth\Events\PhoneAuthFailed;
use Siaoynli\PhoneAuth\Events\PhoneAuthSuccess;
use Siaoynli\PhoneAuth\Events\PhoneCodeSent;
use Siaoynli\PhoneAuth\Models\PhoneAuthLog;
use Siaoynli\PhoneAuth\Models\PhoneVerificationCode;

/**
 * 手机验证码认证服务
 *
 * 通过依赖注入接收 SmsGateway 和配置，不直接依赖全局 config()。
 * 所有业务操作均返回统一格式：['success' => bool, 'message' => string, ...]
 */
class PhoneAuthService
{
    public function __construct(
        protected SmsGateway $smsGateway,
        protected array $config = []
    ) {}

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

        $cooldown = $this->config['code']['resend_cooldown'] ?? 60;

        if ($lastCode && $lastCode->created_at->addSeconds($cooldown)->isFuture()) {
            $retryAfter = (int) now()->diffInSeconds(
                $lastCode->created_at->addSeconds($cooldown)
            );

            PhoneAuthLog::logSendCode($phone, false, '请求过于频繁');

            return [
                'success'     => false,
                'message'     => '请求太频繁，请稍候再试!',
                'code'        => 429,
                'retry_after' => $retryAfter,
            ];
        }

        // 生成验证码
        $code = $this->generateCode();

        // 保存到数据库
        PhoneVerificationCode::create([
            'phone'      => $phone,
            'code'       => $code,
            'attempts'   => 0,
            'expires_at' => now()->addMinutes($this->config['code']['expire'] ?? 5),
        ]);

        // 发送短信
        try {
            $this->smsGateway->send($phone, $code);

            // 触发事件
            PhoneCodeSent::dispatch($phone, $code);

            // 记录审计日志
            PhoneAuthLog::logSendCode($phone, true);

            return ['success' => true, 'message' => '验证码已发送'];
        } catch (\Exception $e) {
            Log::error('PhoneAuth: 发送短信失败', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            PhoneAuthLog::logSendCode($phone, false, $e->getMessage());

            return ['success' => false, 'message' => '发送验证码失败: ' . $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * 验证码登录
     */
    public function login(string $phone, string $code): array
    {
        // 验证手机号
        if (!$this->validatePhone($phone)) {
            PhoneAuthFailed::dispatch($phone, '手机号格式不正确');
            return ['success' => false, 'message' => '手机号格式不正确', 'code' => 400];
        }

        // 获取未过期的验证码记录
        $record = PhoneVerificationCode::where('phone', $phone)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) {
            PhoneAuthFailed::dispatch($phone, '验证码已过期');
            PhoneAuthLog::logVerifyCode($phone, false, '验证码已过期');
            return ['success' => false, 'message' => '验证码已过期，请重新获取', 'code' => 400];
        }

        // 检查尝试次数
        $maxAttempts = $this->config['code']['attempts'] ?? 5;
        if ($record->attempts >= $maxAttempts) {
            $record->delete();
            PhoneAuthFailed::dispatch($phone, '尝试次数过多');
            PhoneAuthLog::logVerifyCode($phone, false, '尝试次数过多');
            return ['success' => false, 'message' => '尝试次数过多，请重新获取验证码', 'code' => 429];
        }

        // 验证验证码
        if ($record->code !== $code) {
            $record->incrementAttempts();
            PhoneAuthFailed::dispatch($phone, '验证码错误');
            PhoneAuthLog::logVerifyCode($phone, false, '验证码错误');
            return ['success' => false, 'message' => '验证码错误', 'code' => 400];
        }

        // 删除已使用的验证码
        $record->delete();

        PhoneAuthLog::logVerifyCode($phone, true);

        // 获取或创建用户
        $userModel  = $this->config['user']['model'] ?? \App\Models\User::class;
        $phoneField = $this->config['user']['phone_field'] ?? 'phone';

        $user = $userModel::where($phoneField, $phone)->first();

        // 自动注册新用户
        if (!$user && ($this->config['features']['register'] ?? false)) {
            $user = $this->registerUser($userModel, $phoneField, $phone);
        }

        if (!$user) {
            PhoneAuthFailed::dispatch($phone, '用户不存在');
            PhoneAuthLog::logLogin($phone, null, false, '用户不存在');
            return ['success' => false, 'message' => '用户不存在', 'code' => 404];
        }

        // 生成 API Token
        $tokenExpiry = $this->config['token']['expire'] ?? (7 * 24 * 60);
        $token = $user->createToken(
            'phone-auth',
            ['*'],
            now()->addMinutes($tokenExpiry)
        )->plainTextToken;

        // 触发登录成功事件
        PhoneAuthSuccess::dispatch($user, $phone, $token);

        // 记录审计日志
        PhoneAuthLog::logLogin($phone, $user->id, true);

        return [
            'success' => true,
            'message' => '登录成功',
            'data'    => [
                'token' => $token,
                'user'  => $user,
            ],
        ];
    }

    /**
     * 自动注册新用户
     */
    protected function registerUser(string $userModel, string $phoneField, string $phone): ?object
    {
        try {
            return $userModel::create([
                'name'  => '用户' . substr($phone, -4),
                $phoneField => $phone,
            ]);
        } catch (\Exception $e) {
            Log::error('PhoneAuth: 自动注册用户失败', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 验证手机号格式
     */
    public function validatePhone(string $phone): bool
    {
        return (bool) preg_match('/^1[3-9]\d{9}$/', $phone);
    }

    /**
     * 生成验证码
     */
    protected function generateCode(): string
    {
        $length = $this->config['code']['length'] ?? 6;
        return str_pad(
            (string) random_int(0, 10 ** $length - 1),
            $length,
            '0',
            STR_PAD_LEFT
        );
    }
}
