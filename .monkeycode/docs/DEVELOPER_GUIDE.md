# AI-mmi 开发者指南

## 项目目的

AI-mmi 是一个智能移民与留学 AI 助手平台，为用户提供 AI 聊天咨询、移民资讯、签证选项、留学申请、社区论坛、付费订阅等功能。该平台部署于 `atonline-appmaker.com`，服务全球有移民和留学需求的用户。

**核心职责**:
- AI 驱动的移民留学咨询(基于 xAI Grok-4-1)
- 用户注册/登录(支持 Google/Facebook 社交登录)
- 五级付费套餐订阅(Stripe 支付)
- 社区论坛和帖子发布
- 文档上传和 AI 分析

## 环境搭建

### 前置条件

- PHP >= 7.3 (推荐 8.0)
- Composer
- MySQL 5.7+
- Node.js >= 14
- NPM

### 安装

```bash
# 克隆仓库
git clone <repo-url>
cd ai-mmi-backup-2025-08-06

# 安装 PHP 依赖
composer install

# 安装前端依赖
npm install

# 配置环境
cp .env.example .env

# 编辑 .env 填入数据库连接等信息

# 生成应用密钥
php artisan key:generate

# 运行数据库迁移
php artisan migrate

# 填充种子数据
php artisan db:seed --class=PlanServiceSeeder

# 编译前端资源
npm run dev
```

### 环境变量

| 变量 | 必需 | 描述 |
|------|------|------|
| `APP_URL` | 是 | 应用根 URL |
| `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | 是 | 数据库连接 |
| `STRIPE_KEY` / `STRIPE_SECRET` | 否 | Stripe 支付密钥 |
| `STRIPE_WEBHOOK_SECRET` | 否 | Stripe Webhook 签名密钥 |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | 否 | Google OAuth |
| `FACEBOOK_CLIENT_ID` / `FACEBOOK_CLIENT_SECRET` | 否 | Facebook OAuth |
| `XAI_API_KEY` | 否 | xAI API 密钥(AI 聊天) |

### 运行

```bash
# 开发服务器
php artisan serve

# 编译前端(监听模式)
npm run watch

# 运行测试
php artisan test
```

## 开发工作流

### 代码结构规范

项目不遵循标准 Laravel 目录约定，有自定义实现：

- **控制器**: 不继承标准 Controller，继承 `CoreController`(前台) 或 `AdminController`(后台) 或 `WebController`(前台基类)
- **模型**: 不使用 Eloquent ORM，继承 `BaseModel` 使用 `DB` 门面直接操作数据库
- **路由**: 绝大部分路由通过 `RouteMapping` 动态映射，URL 段 `/{class}/{function}` 映射到 `Web\{Class}@{function}`
- **视图**: Blade 模板位于 `resources/views/web/`(前台) 和 `resources/views/admin/`(后台)
- **多语言**: 位于 `resources/lang/{locale}/`，语言文件前缀 `_web.php`(前台), `_admin.php`(后台), `_global.php`(全局), `_database.php`(数据表)
- **前端 JS**: 位于 `public/asset/js/web/`(前台) 和 `public/asset/js/admin/`(后台)
- **CSS/JS 自动加载**: `CoreController` 根据控制器和方法名自动加载对应的 CSS/JS 文件

### 添加新页面前台

1. 在 `app/Http/Controllers/Web/` 创建控制器类 `MyPage.php`，继承 `App\Http\Controllers\WebController`
2. 实现 `index()` 方法处理页面逻辑
3. 在 `resources/views/web/` 创建 `my_page.blade.php` 视图
4. 在 `resources/lang/{locale}/_web.php` 添加语言条目
5. 在 `public/asset/js/web/` 创建 `my_page.js` 前端脚本(可选)
6. 访问 `/my_page` 即可(RouteMapping 自动解析)

### 添加新页面后台

1. 在 `app/Http/Controllers/Admin/` 创建控制器类 `MyModule.php`，继承 `App\Http\Controllers\AdminController`
2. 使用 `templateListView()` 和 `templateFormView()` 生成列表和表单页
3. 访问 `/admin/my_module` 即可

### 添加新数据库表

1. 创建迁移文件:
```bash
php artisan make:migration create_new_table
```
2. 编写 `up()` 和 `down()` 方法
3. 运行 `php artisan migrate`
4. 在 `app/Models/` 创建对应的 Model 类，继承 `BaseModel`

### 添加新 AI 聊天功能

修改以下文件:
1. `app/Http/Controllers/Web/Home.php` - `chat()` 和 `callXaiResponses()` 方法
2. `config/conversation_flows.php` - 会话流关键词和评分规则(如需要)
3. `app/Services/ConversationFlowService.php` - 推荐流程逻辑(如需要)

### 修复 Bug 流程

1. 定位 Bug 所在文件和行
2. 如果涉及数据库，先检查迁移文件确认字段定义
3. 如果涉及路由，利用 `RouteMapping` 的日志确认映射是否正确
4. 如果涉及 AI 聊天，检查 `chat_log` 表记录和 xAI API 响应

## 常用命令

| 命令 | 说明 |
|------|------|
| `php artisan serve` | 启动开发服务器(端口 8000) |
| `php artisan migrate` | 运行数据库迁移 |
| `php artisan migrate:rollback` | 回滚上次迁移 |
| `php artisan db:seed` | 填充种子数据 |
| `php artisan route:list` | 列出所有路由(注意:仅有显式路由) |
| `php artisan tinker` | 交互式 PHP 调试 |
| `php artisan test` | 运行 PHPUnit 测试 |
| `php artisan config:cache` | 缓存配置(生产环境) |
| `npm run dev` | 编译前端资源(开发模式) |
| `npm run watch` | 编译前端资源(监听模式) |
| `npm run production` | 编译前端资源(生产模式) |

## 编码规范

### 文件命名

| 类型 | 约定 | 示例 |
|------|------|------|
| 控制器 | PascalCase | `Account_Login.php`, `Visa_Options.php` |
| 模型 | PascalCase | `Member.php`, `Posts.php` |
| 视图 | snake_case | `account_login.blade.php` |
| 前端 JS | snake_case | `account_login.js` |
| 语言文件 | 前缀_模块 | `_web.php`, `_admin.php` |
| 数据库迁移 | YYYY_MM_DD_HHMMSS_description | `2025_01_30_000000_create_table.php` |

### BaseModel 查询模式

不使用 Eloquent 关系和方法链，使用 BaseModel 的辅助方法:

```php
// 查询列表
$model = new Posts();
$model->setWhere('status', 'public');
$model->setOrder('created_at', 'desc');
$result = $model->queryListData($page, $perPage);

// 查询单条
$post = $model->queryOneData($id);

// 插入
$model->queryInsertData(['title' => '...', 'content' => '...']);

// 更新
$model->queryUpdateData($id, ['status' => 'published']);

// 删除(软删除)
$model->queryDeleteData($id);
```

### 控制器方法返回模式

```php
public function index()
{
    // 加载页面(自动选择视图/语言/JS/CSS)
    $this->pageData['key'] = 'value';
    $this->viewData['extra'] = $value;
    $this->pageTitle = '页面标题';
    return $this->loadView();
}
```

### 多语言使用

```php
// PHP 中使用
lang('_web.page_title_key');

// Blade 视图中使用
{{ lang('_web.page_title_key') }}

// JS 中使用(渲染到页面时通过 PHP 注入)
```

### 数据库操作注意事项

- 绝不修改已部署的 migration 文件，只能创建新的 migration
- 使用 BaseModel 的软删除功能(字段 `status` 标记而非物理删除)
- 多语言字段使用 JSON 存储，如 `title` 字段存储 `{"en": "Title", "zh-hant": "標題"}`

## 测试

当前测试覆盖率极低，`tests/Feature/ExampleTest.php` 和 `tests/Unit/ExampleTest.php` 仅为 Laravel 默认模板。

编写新测试:
```bash
# 创建测试
php artisan make:test MyTest

# 运行测试
php artisan test

# 运行指定测试
php artisan test --filter=MyTest
```

测试基类位于 `tests/TestCase.php`，使用 `CreatesApplication` trait。

## 目录速查

| 目录 | 用途 |
|------|------|
| `app/Http/Controllers/Web/` | 前台页面控制器 |
| `app/Http/Controllers/Admin/` | 后台管理控制器 |
| `app/Http/Controllers/Api/` | REST API 控制器 |
| `app/Http/Middleware/` | HTTP 中间件 |
| `app/Models/` | 数据模型(BaseModel 子类) |
| `app/Services/` | 业务服务层 |
| `app/Libraries/` | 第三方库封装(PayPal/PHPExcel/SendGrid) |
| `config/` | 配置文件 |
| `config/app_portal.php` | 核心自定义配置(语言/中间件映射) |
| `config/conversation_flows.php` | 会话流推荐规则 |
| `database/migrations/` | 数据库迁移 |
| `resources/views/web/` | 前台 Blade 视图 |
| `resources/views/admin/` | 后台 Blade 视图 |
| `resources/lang/` | 多语言文件 |
| `public/asset/js/web/` | 前台前端 JS |
| `public/asset/js/admin/` | 后台前端 JS |
| `public/asset/lib/` | 第三方前端库 |
| `routes/web.php` | Web 路由 |
| `routes/api.php` | API 路由 |
