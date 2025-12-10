<?php

namespace Siaoynli\PhoneAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneAuthBinding extends Model
{
  protected $table = 'phone_auth_bindings';

  protected $fillable = [
    'user_id',
    'phone',
    'status',
    'is_primary',
    'bound_at',
    'verified_at',
  ];

  protected $casts = [
    'is_primary' => 'boolean',
    'bound_at' => 'datetime',
    'verified_at' => 'datetime',
  ];

  /**
   * 关联用户
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(config('auth.providers.users.model'));
  }

  /**
   * 检查手机号是否已绑定
   */
  public static function isBound(string $phone): bool
  {
    return static::where('phone', $phone)
      ->where('status', 'verified')
      ->exists();
  }

  /**
   * 获取已验证的绑定
   */
  public static function getVerified(string $phone): ?self
  {
    return static::where('phone', $phone)
      ->where('status', 'verified')
      ->first();
  }

  /**
   * 验证绑定
   */
  public function verify(): void
  {
    $this->update([
      'status' => 'verified',
      'verified_at' => now(),
    ]);
  }

  /**
   * 设置为主手机号
   */
  public function setPrimary(): void
  {
    // 取消其他主手机号
    static::where('user_id', $this->user_id)
      ->update(['is_primary' => false]);

    // 设置当前为主手机号
    $this->update(['is_primary' => true]);
  }
}
