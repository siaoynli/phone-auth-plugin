# 插件系统快速参考卡

## 🚀 5 分钟快速开始

### 第 1 步：创建插件目录

```bash
mkdir -p packages/my-plugin/src/{Controllers,Services,Providers}
cd packages/my-plugin
mkdir -p {routes,config,database/migrations}
```

### 第 2 步：创建主类

**src/MyPlugin.php**

```php
<?php
namespace YourVendor\MyPlugin;
use App\Plugins\AbstractPlugin;

class MyPlugin extends AbstractPlugin
{
    public function getName(): string { return 'My Plugin'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDescription(): string { return 'Description'; }
}
```

### 第 3 步：创建路由

**routes/web.php**

```php
<?php
use Illuminate\Support\Facades\Route;
Route::post('/action', function () {
    return ['success' => true];
});
```

### 第 4 步：创建配置

**config/plugin.php**

```php
<?php
return [
    'enabled' => true,
    'route_prefix' => 'api/my-plugin',
    'middleware' => ['api'],
];
```

### 第 5 步：注册插件

在 **composer.json** 中：

```json
{
    "autoload": {
        "psr-4": {
            "YourVendor\\MyPlugin\\": "packages/my-plugin/src/"
        }
    }
}
```

在 **config/plugins.php** 中：

```php
<?php
return [
    'yourvendor/my-plugin' => 'YourVendor\\MyPlugin\\MyPlugin',
];
```

### 第 6 步：部署

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan plugin:list
```

---

## 📋 核心文件清单

### 必需文件

```
src/
├── MyPlugin.php                      ✅ 必需
├── Controllers/
│   └── MyController.php
├── Services/
│   └── MyService.php
└── Providers/
    └── MyPluginServiceProvider.php

routes/
└── web.php                          ✅ 必需

config/
└── plugin.php                       ✅ 必需

composer.json                        ✅ 必需
```

### 可选文件

```
database/migrations/
├── 2024_01_01_000000_create_table.php
└── ...

resources/
├── views/
│   └── template.blade.php
└── assets/
    ├── css/style.css
    └── js/app.js

tests/
├── Feature/
└── Unit/

README.md
```

---

## 🔧 常用命令

```bash
# 列出所有插件
php artisan plugin:list

# 发布插件资源
php artisan plugin:publish

# 发布特定插件
php artisan plugin:publish vendor/plugin-name

# 运行迁移
php artisan migrate

# 清除缓存
php artisan optimize:clear

# 清除路由缓存
php artisan route:clear

# 进入调试
php artisan tinker
```

---

## 💻 常用代码片段

### 获取服务

```php
// 方式 1
$service = app('my-plugin.service');

// 方式 2
$service = app(\YourVendor\MyPlugin\Services\MyService::class);

// 方式 3（推荐）
public function __construct(\YourVendor\MyPlugin\Services\MyService $service)
{
    $this->service = $service;
}
```

### 注册服务

```php
// 在服务提供者中
$this->app->singleton('my-plugin.service', function ($app) {
    return new MyService(config('my-plugin', []));
});
```

### 访问配置

```php
// 方式 1
$config = config('my-plugin');
$value = config('my-plugin.key');

// 方式 2
$service = app('my-plugin.service');
$config = $service->getConfig();
```

### 创建响应

```php
return response()->json([
    'success' => true,
    'message' => 'Success message',
    'data' => [],
]);
```

### 记录日志

```php
\Log::info('Action completed', [
    'plugin' => 'my-plugin',
    'data' => $data,
]);
```

---

## 🎯 开发流程

```
① 创建目录结构
   ↓
② 实现 AbstractPlugin
   ↓
③ 创建配置文件
   ↓
④ 创建路由文件
   ↓
⑤ 创建控制器和服务
   ↓
⑥ 创建服务提供者
   ↓
⑦ 在 composer.json 中配置 autoload
   ↓
⑧ 在 config/plugins.php 中注册
   ↓
⑨ 运行 composer dump-autoload
   ↓
⑩ 测试和调试
   ↓
⑪ 发布迁移
   ↓
⑫ 发布和分发
```

---

## 📁 目录结构速查

### 最小化结构

```
packages/my-plugin/
├── src/
│   └── MyPlugin.php
├── routes/
│   └── web.php
├── config/
│   └── plugin.php
└── composer.json
```

### 标准结构

```
packages/my-plugin/
├── src/
│   ├── MyPlugin.php
│   ├── Controllers/
│   ├── Services/
│   ├── Models/
│   └── Providers/
├── routes/web.php
├── config/plugin.php
├── database/migrations/
├── resources/{views,assets}/
├── tests/{Feature,Unit}/
├── composer.json
└── README.md
```

### 完整结构

```
packages/my-plugin/
├── src/
│   ├── MyPlugin.php
│   ├── Controllers/
│   ├── Services/
│   ├── Models/
│   ├── Providers/
│   ├── Traits/
│   ├── Rules/
│   ├── Events/
│   ├── Jobs/
│   └── Exceptions/
├── routes/
│   ├── web.php
│   └── api.php
├── config/plugin.php
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── views/
│   ├── assets/
│   └── lang/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .gitignore
├── composer.json
├── phpunit.xml
├── README.md
└── LICENSE
```

---

## 🐛 常见错误速查

| 错误                    | 原因                           | 解决                         |
| ----------------------- | ------------------------------ | ---------------------------- |
| class_exists 返回 false | 自动加载未配置                 | `composer dump-autoload -o`  |
| Unresolvable dependency | 容器无法解析参数               | 在闭包中显式传递             |
| Plugin not found        | 未在 config/plugins.php 中注册 | 添加到配置                   |
| Route 404               | 插件未加载或路由缓存           | `php artisan route:clear`    |
| Config not found        | 未发布配置                     | `php artisan plugin:publish` |

---

## ⚙️ 配置示例

### 最小配置

```php
<?php
return [
    'enabled' => true,
];
```

### 标准配置

```php
<?php
return [
    'enabled' => env('MY_PLUGIN_ENABLED', true),
    'route_prefix' => 'api/my-plugin',
    'middleware' => ['api'],
    'setting1' => env('MY_PLUGIN_SETTING1', 'default'),
];
```

### 完整配置

```php
<?php
return [
    'enabled' => env('MY_PLUGIN_ENABLED', true),
    'route_prefix' => 'api/my-plugin',
    'middleware' => ['api'],

    'database' => [
        'connection' => env('MY_PLUGIN_DB', 'mysql'),
    ],

    'cache' => [
        'ttl' => env('MY_PLUGIN_CACHE_TTL', 3600),
    ],

    'features' => [
        'feature1' => true,
        'feature2' => false,
    ],
];
```

---

## 📊 性能检查清单

-   [ ] 使用 singleton 而不是每次创建新实例
-   [ ] 缓存配置而不是每次读取
-   [ ] 使用数据库查询优化（eager loading）
-   [ ] 添加适当的索引
-   [ ] 使用队列处理长时间操作
-   [ ] 监控日志大小
-   [ ] 定期清理临时数据

---

## 🧪 测试模板

```php
<?php

namespace YourVendor\MyPlugin\Tests\Feature;

use Tests\TestCase;

class PluginTest extends TestCase
{
    public function test_plugin_is_registered()
    {
        $this->assertTrue(
            app('plugin-manager')->hasPlugin('yourvendor/my-plugin')
        );
    }

    public function test_api_endpoint()
    {
        $response = $this->postJson('/api/my-plugin/action');
        $response->assertSuccessful();
    }
}
```

---

## 📚 学习路径

### 初级

1. ✅ 理解插件概念
2. ✅ 创建简单插件
3. ✅ 使用插件 API

### 中级

1. ✅ 创建复杂服务
2. ✅ 使用数据库迁移
3. ✅ 编写测试

### 高级

1. ✅ 发布到 Packagist
2. ✅ 性能优化
3. ✅ 安全加固

---

## 🔗 重要链接

### 官方文档

-   [Laravel 服务容器](https://laravel.com/docs/11.x/container)
-   [Laravel 路由](https://laravel.com/docs/11.x/routing)
-   [Laravel 数据库](https://laravel.com/docs/11.x/database)

### Composer

-   [Packagist](https://packagist.org)
-   [发布包指南](https://getcomposer.org/doc/02-libraries.md)

---

## 💡 最佳实践速览

```
✅ 使用清晰的命名规范
✅ 编写完整的文档
✅ 添加单元测试
✅ 使用类型提示
✅ 添加错误处理
✅ 记录日志
✅ 使用版本控制
✅ 定期发布更新
```

---

## 🎁 完整检查清单

### 创建阶段

-   [ ] 创建目录结构
-   [ ] 实现主插件类
-   [ ] 创建配置文件
-   [ ] 创建路由文件
-   [ ] 创建控制器
-   [ ] 创建服务

### 集成阶段

-   [ ] 配置 composer.json
-   [ ] 注册 config/plugins.php
-   [ ] 更新自动加载
-   [ ] 清除缓存

### 测试阶段

-   [ ] 验证插件加载
-   [ ] 测试路由
-   [ ] 测试服务
-   [ ] 查看日志

### 发布阶段

-   [ ] 运行迁移
-   [ ] 发布资源
-   [ ] 编写文档
-   [ ] 发布包

---
