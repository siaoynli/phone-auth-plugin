<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * 执行迁移
   */
  public function up(): void
  {
    Schema::create('phone_verification_codes', function (Blueprint $table) {
      // 主键
      $table->id();

      // 手机号码
      $table->string('phone', 20)->index();

      // 验证码
      $table->string('code', 10);

      // 尝试次数
      $table->integer('attempts')->default(0);

      // 过期时间
      $table->timestamp('expires_at')->index();

      // 时间戳
      $table->timestamps();

      // 复合索引，加快查询性能
      $table->index(['phone', 'expires_at']);
    });
  }

  /**
   * 回滚迁移
   */
  public function down(): void
  {
    Schema::dropIfExists('phone_verification_codes');
  }
};
