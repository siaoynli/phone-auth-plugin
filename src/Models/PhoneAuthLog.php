<?php

namespace Siaoynli\PhoneAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneAuthLog extends Model
{
  protected $table = 'phone_auth_logs';

  protected $fillable = [
    'phone',
    'user_id',
    'action',
    'success',
    'reason',
    'ip_address',
    'user_agent',
  ];

  protected $casts = [
    'success' => 'boolean',
  ];

  /**
   * 关联用户
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(config('auth.providers.users.model'));
  }

  /**
   * 记录发送验证码
   */
  public static function logSendCode(string $phone, bool $success, ?string $reason = null): void
  {
    static::create([
      'phone' => $phone,
      'action' => 'send_code',
      'success' => $success,
      'reason' => $reason,
      'ip_address' => request()->ip(),
      'user_agent' => request()->userAgent(),
    ]);
  }

  /**
   * 记录验证验证码
   */
  public static function logVerifyCode(string $phone, bool $success, ?string $reason = null): void
  {
    static::create([
      'phone' => $phone,
      'action' => 'verify_code',
      'success' => $success,
      'reason' => $reason,
      'ip_address' => request()->ip(),
      'user_agent' => request()->userAgent(),
    ]);
  }

  /**
   * 记录登录
   */
  public static function logLogin(string $phone, ?int $userId, bool $success, ?string $reason = null): void
  {
    static::create([
      'phone' => $phone,
      'user_id' => $userId,
      'action' => 'login',
      'success' => $success,
      'reason' => $reason,
      'ip_address' => request()->ip(),
      'user_agent' => request()->userAgent(),
    ]);
  }
}
