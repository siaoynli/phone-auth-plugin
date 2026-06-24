<?php

namespace Siaoynli\PhoneAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Siaoynli\PhoneAuth\Contracts\SmsGateway;
use Siaoynli\PhoneAuth\Drivers\AliyunSmsDriver;
use Siaoynli\PhoneAuth\Drivers\DxSmsDriver;
use Siaoynli\PhoneAuth\Drivers\LogSmsDriver;
use Siaoynli\PhoneAuth\Drivers\MockSmsDriver;
use Siaoynli\PhoneAuth\Services\PhoneAuthService;

/**
 * PhoneAuth Service Provider
 *
 * 负责容器绑定（SmsGateway 驱动、PhoneAuthService 单例）。
 *
 * 配置合并和资源发布由 AbstractPlugin + PluginPublisher 统一处理，
 * 此处不再重复。
 */
class PhoneAuthServiceProvider extends ServiceProvider
{
    /**
     * 配置键 — 与 AbstractPlugin 的 mergeConfig() 保持一致
     */
    protected const CONFIG_KEY = 'plugins.siaoynli-phone-auth-plugin';

    /**
     * 注册服务 — 绑定容器
     */
    public function register(): void
    {
        $this->bindSmsGateway();
        $this->bindPhoneAuthService();
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 由 PluginPublisher 统一处理配置/迁移/视图/资源发布
        // 此处保留作为插件特有的 boot 逻辑扩展点
    }

    /**
     * 绑定短信网关驱动（单例）
     *
     * 根据配置中的 sms.driver 字段选择具体驱动实现。
     */
    protected function bindSmsGateway(): void
    {
        $this->app->singleton(SmsGateway::class, function ($app) {
            $config = config(static::CONFIG_KEY, []);
            $driver = $config['sms']['driver'] ?? 'log';

            return match ($driver) {
                'aliyun' => new AliyunSmsDriver($config),
                'dxsms'  => new DxSmsDriver($config),
                'mock'   => new MockSmsDriver($config),
                default  => new LogSmsDriver($config),
            };
        });
    }

    /**
     * 绑定 PhoneAuthService（单例）
     */
    protected function bindPhoneAuthService(): void
    {
        $this->app->singleton(PhoneAuthService::class, function ($app) {
            return new PhoneAuthService(
                $app->make(SmsGateway::class),
                config(static::CONFIG_KEY, [])
            );
        });
    }
}
