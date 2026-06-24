<?php

namespace Siaoynli\PhoneAuth;

use Siaoynli\Plugins\AbstractPlugin;

/**
 * 手机验证码登录插件
 *
 * 继承 AbstractPlugin，充分利用框架的延迟加载、自动发现、
 * 配置合并和资源发布机制。
 *
 * 生命周期：
 * - register(): 父类自动合并配置 + 发现并注册 src/Providers/ 下的 ServiceProvider
 * - boot(): 插件启动阶段，可注册事件监听等
 * - registerRoutes(): 父类自动加载 routes/ 目录下的路由文件
 */
class PhoneAuthPlugin extends AbstractPlugin
{
    /**
     * 启动阶段 — 注册事件监听等
     *
     * 此时所有 ServiceProvider 均已注册完毕，可安全使用任何服务。
     */
    public function boot(): void
    {
        // 插件特有的启动逻辑可在此添加
        // 例如：注册事件监听器、中间件等
    }
}
