<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * 执行迁移
   * 用于记录用户手机号与账户的绑定关系
   */
  public function up(): void
  {
    Schema::create('phone_auth_bindings', function (Blueprint $table) {
      $table->id();

      // 用户 ID
      $table->unsignedBigInteger('user_id')->index();

      // 手机号
      $table->string('phone', 20)->unique()->index();

      // 绑定状态：pending, verified, suspended
      $table->string('status', 20)->default('verified');

      // 是否为主手机号
      $table->boolean('is_primary')->default(false);

      // 绑定时间
      $table->timestamp('bound_at')->useCurrent();

      // 验证时间
      $table->timestamp('verified_at')->nullable();

      // 时间戳
      $table->timestamps();
      // 复合索引
      $table->index(['user_id', 'status']);
    });
  }

  /**
   * 回滚迁移
   */
  public function down(): void
  {
    Schema::dropIfExists('phone_auth_bindings');
  }
};
