<?php

return [
  // 是否启用
  'enabled' => env('PHONE_AUTH_PLUGIN_ENABLED', true),
  'product' => env('APP_CNNAME', '杭州网智能媒资系统'),

  // 路由前缀
  'route_prefix' => '/api/plugin/phone-auth',
  // 中间件
  'middleware' => ['api'],
  // 验证码配置
  'code' => [
    'length' => 6,              // 验证码长度
    'expire' => 5,              // 过期时间（分钟）
    'attempts' => 5,            // 最多尝试次数
    'resend_cooldown' => 60,    // 重新发送冷却时间（秒）
  ],

  // Token 配置
  'token' => [
    'expire' => 7 * 24 * 60,    // Token 过期时间（分钟）
  ],

  // 短信配置
  'sms' => [
    'driver' => env('PHONE_AUTH_SMS_DRIVER', 'log'),

    // 阿里云配置
    'aliyun' => [
      'access_key_id' => env('ACCESS_KEY_ID'),
      'access_key_secret' => env('ACCESS_KEY_SECRET'),
      'sign_name' => env('SMS_SIGN_NAME', ''),
    ],

    // 电信配置
    'dxsms' => [
      'enable' => env("DX_SMS_ENABLED", true),
      'api_key' => env('DXSMS_API_KEY', ''),
      'siid' => env('DXSMS_SIID', ''),
      'user' => env('DXSMS_USER', ''),
    ],
  ],

  // 用户字段配置
  'user' => [
    'model' => \App\Models\User::class,
    'phone_field' => 'phone',  // 用户表中的手机号字段
  ],

  // 功能开关
  'features' => [
    'register' => true,         // 是否在登录时自动注册新用户
    'bind' => true,             // 是否支持绑定已有用户
    'logout' => true,           // 是否支持登出
  ],
];
