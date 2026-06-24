<?php

namespace Siaoynli\PhoneAuth\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siaoynli\PhoneAuth\Rules\PhoneRule;
use Siaoynli\PhoneAuth\Services\PhoneAuthService;

/**
 * 手机验证码认证控制器
 *
 * 所有端点返回统一的 JSON 响应格式。
 * HTTP 状态码由服务层返回的 code 字段决定。
 */
class PhoneAuthController extends Controller
{
    protected const CONFIG_KEY = 'plugins.siaoynli-phone-auth-plugin';

    public function __construct(
        protected PhoneAuthService $service
    ) {}

    /**
     * 发送验证码
     */
    public function sendCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', new PhoneRule()],
        ], [
            'phone.required' => '手机号不能为空',
        ]);

        $result = $this->service->sendCode($validated['phone']);

        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * 验证码登录
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', new PhoneRule()],
            'code'  => ['required', 'string', 'size:6'],
        ], [
            'phone.required' => '手机号不能为空',
            'code.required'  => '验证码不能为空',
            'code.size'      => '验证码长度必须为 6 位',
        ]);

        $result = $this->service->login($validated['phone'], $validated['code']);

        return response()->json($result, $result['code'] ?? 200);
    }

    /**
     * 退出登录
     */
    public function logout(Request $request): JsonResponse
    {
        if (!config(self::CONFIG_KEY . '.features.logout', true)) {
            return response()->json([
                'success' => false,
                'message' => '退出功能已禁用',
            ], 403);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => '退出成功',
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user(),
        ]);
    }
}
