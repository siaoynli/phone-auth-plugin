<?php

namespace Siaoynli\PhoneAuth\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class PhoneAuthServiceProvider extends ServiceProvider
{
  /**
   * 注册服务
   */
  public function register(): void
  {
    $this->registerConfig();
  }

  /**
   * 启动服务
   */
  public function boot(): void
  {
    $this->publishConfig();
    $this->publishMigrations();
    $this->publishViews();
    $this->publishAssets();
  }

  /**
   * 注册配置
   */
  protected function registerConfig(): void
  {
    $configPath = __DIR__ . '/../../config/plugin.php';
    $this->mergeConfigFrom($configPath, 'phone-auth');
  }

  /**
   * 发布配置
   */
  protected function publishConfig(): void
  {
    $this->publishes([
      __DIR__ . '/../../config/plugin.php' => config_path('phone-auth.php'),
    ], 'phone-auth-config');
  }

  /**
   * 发布迁移文件
   */
  protected function publishMigrations(): void
  {
    $this->publishes([
      __DIR__ . '/../../database/migrations' => database_path('migrations'),
    ], 'phone-auth-migrations');
  }

  /**
   * 发布视图
   */
  protected function publishViews(): void
  {
    $this->publishes([
      __DIR__ . '/../../resources/views' => resource_path('views/phone-auth'),
    ], 'phone-auth-views');
  }

  /**
   * 发布资源
   */
  protected function publishAssets(): void
  {
    $this->publishes([
      __DIR__ . '/../../resources/assets' => public_path('phone-auth'),
    ], 'phone-auth-assets');
  }
}
