<?php

namespace Siaoynli\PhoneAuth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Siaoynli\PhoneAuth\Rules\PhoneRule;
use Siaoynli\PhoneAuth\Services\PhoneAuthService;

class PhoneAuthController extends Controller
{
  protected $service;

  public function __construct(PhoneAuthService $service)
  {
    $this->service = $service;
  }

  /**
   * 发送验证码
   */
  public function sendCode(Request $request)
  {
    $validated = $request->validate([
      'phone' => ['required', 'string', new PhoneRule()],
    ], [
      'phone.required' => '手机号不能为空',
    ]);

    $result = $this->service->sendCode($validated['phone']);

    $status = $result['code'] ?? 200;
    return response()->json($result, $status);
  }

  /**
   * 验证码登录
   */
  public function login(Request $request)
  {
    $validated = $request->validate([
      'phone' => ['required', 'string', new PhoneRule()],
      'code' => ['required', 'string', 'size:6'],
    ], [
      'phone.required' => '手机号不能为空',
      'code.required' => '验证码不能为空',
      'code.size' => '验证码长度必须为 6 位',
    ]);

    $result = $this->service->login($validated['phone'], $validated['code']);

    $status = $result['code'] ?? 200;
    return response()->json($result, $status);
  }

  /**
   * 退出登录
   */
  public function logout(Request $request)
  {
    if (config('plugins.yourvendor-phone-auth-plugin.features.logout')) {
      $request->user()->currentAccessToken()->delete();
      return response()->json(['success' => true, 'message' => '退出成功']);
    }

    return response()->json([
      'success' => false,
      'message' => '退出功能已禁用',
    ], 403);
  }

  /**
   * 获取当前用户
   */
  public function profile(Request $request)
  {
    return response()->json([
      'success' => true,
      'data' => $request->user(),
    ]);
  }
}
