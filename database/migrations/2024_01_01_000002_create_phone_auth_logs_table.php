<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * 执行迁移
   * 用于记录登录尝试和验证码发送记录
   */
  public function up(): void
  {
    Schema::create('phone_auth_logs', function (Blueprint $table) {
      $table->id();

      // 手机号
      $table->string('phone', 20)->index();

      // 用户 ID
      $table->unsignedBigInteger('user_id')->nullable()->index();

      // 操作类型：send_code, verify_code, login
      $table->string('action', 20)->index();

      // 是否成功
      $table->boolean('success')->default(false);

      // 失败原因
      $table->string('reason')->nullable();

      // IP 地址
      $table->string('ip_address', 45)->nullable();

      // 用户代理
      $table->text('user_agent')->nullable();

      // 时间戳
      $table->timestamps();

      // 复合索引
      $table->index(['phone', 'action', 'created_at']);
    });
  }

  /**
   * 回滚迁移
   */
  public function down(): void
  {
    Schema::dropIfExists('phone_auth_logs');
  }
};
