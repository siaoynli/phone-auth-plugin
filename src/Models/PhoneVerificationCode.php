<?php

namespace Siaoynli\PhoneAuth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PhoneVerificationCode extends Model
{
  protected $table = 'phone_verification_codes';

  protected $fillable = [
    'phone',
    'code',
    'attempts',
    'expires_at',
  ];

  protected $casts = [
    'expires_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * 范围查询：获取未过期的验证码
   */
  public function scopeValid(Builder $query): Builder
  {
    return $query->where('expires_at', '>', now());
  }

  /**
   * 范围查询：获取已过期的验证码
   */
  public function scopeExpired(Builder $query): Builder
  {
    return $query->where('expires_at', '<=', now());
  }

  /**
   * 范围查询：获取特定手机号的验证码
   */
  public function scopeForPhone(Builder $query, string $phone): Builder
  {
    return $query->where('phone', $phone);
  }

  /**
   * 检查验证码是否有效
   */
  public function isValid(): bool
  {
    return $this->expires_at > now() && $this->attempts < 5;
  }

  /**
   * 检查验证码是否已过期
   */
  public function isExpired(): bool
  {
    return $this->expires_at <= now();
  }

  /**
   * 增加尝试次数
   */
  public function incrementAttempts(): void
  {
    $this->increment('attempts');
  }
}
