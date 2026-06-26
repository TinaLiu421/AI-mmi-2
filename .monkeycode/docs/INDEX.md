# AI-mmi 项目文档

AI-mmi 是一个基于 Laravel 8 的智能移民与留学 AI 助手全栈 Web 应用。本文档涵盖系统架构、接口定义、开发者指南和核心概念，帮助开发者和新成员快速理解和上手项目。

**快速链接**: [架构](./ARCHITECTURE.md) | [接口](./INTERFACES.md) | [开发者指南](./DEVELOPER_GUIDE.md)

---

## 核心文档

### [架构](./ARCHITECTURE.md)
系统设计、技术栈、组件结构和数据流程。包含子系统说明、架构图和请求生命周期图。

### [接口](./INTERFACES.md)
Web 路由表、REST API 端点、Stripe Webhook 和多语言参数说明。集成或使用本系统的参考。

### [开发者指南](./DEVELOPER_GUIDE.md)
环境搭建、开发工作流、编码规范和常见任务。贡献者必读。

---

## 模块

| 模块 | 描述 |
|------|------|
| [控制器层 (Controllers)](./模块/Controllers.md) | 前台/后台/API 控制器，URL 路由映射引擎 |
| [数据模型 (Models)](./模块/Models.md) | BaseModel 基类和业务实体模型 |
| [业务服务 (Services)](./模块/Services.md) | AI 聊天、会话流推荐、文档分析、RAG |
| [中间件 (Middleware)](./模块/Middleware.md) | 认证、CSRF、IP 白名单等请求过滤 |
| [配置系统 (Config)](./模块/Config.md) | 应用配置、多语言、会话流规则 |
| [前端资源 (Assets)](./模块/Assets.md) | Blade 视图、JavaScript、CSS、第三方库 |
| [数据库 (Database)](./模块/Database.md) | 迁移文件、种子数据、表结构 |

---

## 核心概念

| 概念 | 描述 |
|------|------|
| [会员 (Member)](./专有概念/Member.md) | 前台用户体系，包含注册/登录/社交登录/代理/服务商 |
| [套餐与订阅](./专有概念/Subscription.md) | 五级付费套餐、Stripe 支付、会话流智能推荐 |
| [AI 聊天](./专有概念/AI-Chat.md) | xAI 大模型集成、知识库搜索、多语言回复 |
| [路由映射](./专有概念/RouteMapping.md) | URL 自动映射到控制器和方法的引擎 |
| [动态模板](./专有概念/DynamicTemplate.md) | 后台 CRUD 页面的模板化生成 |

---

## 入门指南

### 项目新人
按此路径学习：
1. **[架构](./ARCHITECTURE.md)** - 了解系统全局
2. **[核心概念](#核心概念)** - 学习领域术语
3. **[开发者指南](./DEVELOPER_GUIDE.md)** - 搭建开发环境
4. **[接口](./INTERFACES.md)** - 了解路由和 API

### 需要添加功能
1. **[开发者指南 - 编码规范](./DEVELOPER_GUIDE.md#编码规范)** - 代码风格和约定
2. **[模块文档](#模块)** - 对应模块的 README
3. **[接口文档](./INTERFACES.md)** - 路由和 API 参考

---

## 快速参考

### 命令

```bash
php artisan serve           # 启动开发服务器
npm run dev                 # 编译前端资源
php artisan migrate         # 运行数据库迁移
php artisan db:seed --class=PlanServiceSeeder  # 填充种子数据
php artisan test            # 运行测试
```

### 重要文件

| 文件 | 目的 |
|------|------|
| `app/Http/Controllers/RouteMapping.php` | URL 路由映射引擎 |
| `app/Http/Controllers/CoreController.php` | 前台控制器基类(849行) |
| `app/Http/Controllers/AdminController.php` | 后台控制器基类(714行) |
| `app/Http/Controllers/Web/Home.php` | 首页 + AI 聊天核心 |
| `app/Models/BaseModel.php` | 数据模型基类(852行) |
| `app/Models/Member.php` | 会员模型(1148行) |
| `app/Services/ConversationFlowService.php` | 会话流推荐服务(1211行) |
| `config/app_portal.php` | 核心自定义配置 |
| `config/conversation_flows.php` | 会话流规则配置(1211行) |
| `routes/web.php` | Web 路由定义 |
| `database/migrations/` | 数据库迁移文件 |
