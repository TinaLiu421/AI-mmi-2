# 配置系统 (Config)

系统配置文件，包含 Laravel 标准配置和项目自定义配置。

## 结构

```
config/
├── app.php                    # Laravel 核心配置(名称/环境/URL/Providers/别名)
├── app_portal.php             # 项目核心自定义配置(语言/中间件映射/UID)
├── auth.php                   # 认证守卫和密码重置
├── broadcasting.php           # 事件广播
├── cache.php                  # 缓存驱动(file/redis/memcached)
├── conversation_flows.php     # AI 会话流推荐规则(1211行)
├── cors.php                   # CORS 跨域配置
├── database.php               # MySQL 数据库连接
├── filesystems.php            # 文件存储(local/public/s3)
├── hashing.php                # 哈希加密
├── logging.php                # 日志通道
├── mail.php                   # 邮件配置(SMTP/SendGrid)
├── queue.php                  # 队列配置
├── sanctum.php                # Sanctum API 认证
├── services.php               # 第三方服务(Stripe/Google/Facebook/Mailgun)
├── session.php                # Session 配置
└── view.php                   # 视图编译路径
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `app_portal.php` | 核心自定义配置: 支持语言列表(en/zh-hant/zh-hans)、默认语言、中间件映射(`admin => admin.authn`)、应用 UID |
| `conversation_flows.php` | 最大的配置文件(1211行): 定义 AI 聊天触发套餐升级推荐的评分系统(混合专家/VIP/Premium Confidence/学习辅助)、关键词规则、行为信号权重 |
| `services.php` | 第三方服务密钥: Stripe(key/secret/webhook)、Google OAuth、Facebook OAuth、Mailgun、Postmark、SES |
| `cors.php` | CORS 配置: 路径 `api/*`, 允许所有来源和 HTTP 方法 |
| `sanctum.php` | API Token 认证: stateful domains 列表、token 过期时间 |

## 关键配置说明

### app_portal.php

```php
return [
    'languages' => ['en', 'zh-hant', 'zh-hans'],  // 支持的语言
    'default_language' => 'en',                     // 默认语言
    'middleware' => [
        'admin' => 'admin.authn',                   // 后台中间件别名
    ],
    'app_uid' => 'xxx',                             // 应用唯一标识
];
```

### conversation_flows.php

会话流规则的核心配置结构:
```php
return [
    'flows' => [
        'ai_smart' => [
            'trigger_score' => 5,           // 触发阈值
            'keywords' => [...],            // 关键词列表和权重
            'templates' => [                // 多语言提示模板
                'en' => '...',
                'zh-hant' => '...',
                'zh-hans' => '...',
            ],
        ],
        // hybrid, premium, vip 类似结构...
    ],
    'cooldown' => [
        'same_type_hours' => 24,            // 同类型冷却时间
        'cross_type_hours' => 8,            // 跨类型冷却时间
        'post_upgrade_days' => 7,           // 升级后不推荐天数
    ],
];
```

### services.php

第三方服务配置(仅列出配置键，值从 .env 读取):
- `stripe.key` / `stripe.secret` / `stripe.webhook.secret`
- `google.client_id` / `google.client_secret` / `google.redirect`
- `facebook.client_id` / `facebook.client_secret` / `facebook.redirect`
- `mailgun.domain` / `mailgun.secret`
- `postmark.token`
- `ses.key` / `ses.secret` / `ses.region`

## 依赖

**本模块依赖**:
- `.env` 环境变量(通过 `env()` 函数读取)
- Laravel Config 门面

**依赖本模块的**:
- 整个应用通过 `config()` 函数读取配置
- `ConversationFlowService` 依赖 `conversation_flows.php`
- `AdminAuthn` 中间件依赖 `app_portal.php` 的中间件映射
- Stripe/Socialite/邮件等服务依赖 `services.php`

## 注意事项

1. 配置缓存后(`php artisan config:cache`), `.env` 变更不生效，需重新缓存
2. `conversation_flows.php` 是最大的配置文件，修改后需重启 PHP 进程
3. CORS 当前允许所有来源(开发/演示配置)，生产环境需限制
4. Sanctum stateful domains 需根据实际域名配置
