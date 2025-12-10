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
    if (File::isFile(__DIR__ . '/../../config/plugin.php')) {
      $configPath = __DIR__ . '/../../config/plugin.php';
      $this->mergeConfigFrom($configPath, 'phone-auth');
    }
  }

  /**
   * 发布配置
   */
  protected function publishConfig(): void
  {
    if (File::isFile(__DIR__ . '/../../config/plugin.php')) {
      $this->publishes([
        __DIR__ . '/../../config/plugin.php' =>  config_path('plugins/' . $this->getPluginName() . '.php'),
      ], 'phone-auth-config');
    }
  }

  /**
   * 发布迁移文件
   */
  protected function publishMigrations(): void
  {
    if (File::isDirectory(__DIR__ . '/../../database/migrations')) {
      $this->publishes([
        __DIR__ . '/../../database/migrations' => database_path('migrations'),
      ], 'phone-auth-migrations');
    }
  }

  /**
   * 发布视图
   */
  protected function publishViews(): void
  {
    if (File::isDirectory(__DIR__ . '/../../resources/views')) {
      $this->publishes([
        __DIR__ . '/../../resources/views' => resource_path('views/plugins/' . $this->getPluginName()),
      ], 'phone-auth-views');
    }
  }

  /**
   * 发布资源
   */
  protected function publishAssets(): void
  {
    if (File::isDirectory(__DIR__ . '/../../resources/assets')) {
      $this->publishes([
        __DIR__ . '/../../resources/assets' => public_path('plugins/' . $this->getPluginName()),
      ], 'phone-auth-assets');
    }
  }

  /**
   * 获取插件名称
   */
  public function getPluginName(): string
  {
    $composerFile = $this->resolvePath() . '/composer.json';
    if (File::exists($composerFile)) {
      $composer = json_decode(File::get($composerFile), true);
      $name = $composer['name'] ?? 'siaoynli/phone-auth-plugin';
      return str_replace('/', '-', $name);
    }
    return 'unknown';
  }

  /**
   * 解析插件的基础路径
   */
  protected function resolvePath(): string
  {
    $reflection = new \ReflectionClass($this);
    $pluginDir = dirname($reflection->getFileName());

    // 向上遍历找到插件的根目录
    while ($pluginDir !== '/') {
      if (File::exists($pluginDir . '/composer.json')) {
        return $pluginDir;
      }
      $pluginDir = dirname($pluginDir);
    }

    return dirname($reflection->getFileName());
  }
}
