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
    // 检查表是否存在，避免在多个插件中重复添加
    if (Schema::hasTable('users')) {
      Schema::table('users', function (Blueprint $table) {
        // 如果 phone 字段不存在，则添加
        if (!Schema::hasColumn('users', 'phone')) {
          $table->string('phone', 20)
            ->nullable()
            ->unique()
            ->after('email')
            ->comment('手机号码');
        }
      });
    }
  }

  /**
   * 回滚迁移
   */
  public function down(): void
  {
    if (Schema::hasTable('users')) {
      Schema::table('users', function (Blueprint $table) {
        if (Schema::hasColumn('users', 'phone')) {
          $table->dropUnique(['phone']);
          $table->dropColumn('phone');
        }
      });
    }
  }
};
