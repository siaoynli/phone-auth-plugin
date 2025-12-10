<?php

namespace Siaoynli\PhoneAuth;

use Siaoynli\Plugins\AbstractPlugin;

class PhoneAuthPlugin extends AbstractPlugin
{
  public function getName(): string
  {
    return '手机验证码登录';
  }

  public function getVersion(): string
  {
    return '1.0.8';
  }

  public function getDescription(): string
  {
    return '提供基于手机号和验证码的登录功能';
  }


  /**
   * 加载配置文件
   */
  public function loadConfig(): void
  {
    $this->config = config('plugins.siaoynli-phone-auth-plugin', []);
    $this->enabled = $this->config['enabled'] ?? false;
  }


  /**
   * 注册插件
   */
  public function register(): void
  {
    parent::register();

    try {
      // 注册插件的服务提供者
      app()->register(\Siaoynli\PhoneAuth\Providers\PhoneAuthServiceProvider::class);

      \Log::info('✓ PhoneAuthPlugin registered');
    } catch (\Exception $e) {
      \Log::error('Error registering PhoneAuthPlugin: ' . $e->getMessage());
    }
  }
}
