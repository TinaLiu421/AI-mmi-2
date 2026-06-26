# AI-mmi 接口文档

## 概述

AI-mmi 的接口分为三类：动态路由映射的 Web 页面接口、显式定义的 REST API 接口、以及 Stripe Webhook 回调接口。

## Web 路由

### 动态路由映射规则

系统通过 `app/Http/Controllers/RouteMapping.php` 将 URL 自动映射到控制器方法。规则如下：

| URL 模式 | 控制器命名空间 | 示例 |
|---------|-------------|------|
| `/admin/{class}/{function}/{params...}` | `Admin\{Class}` | `/admin/members/index` → `Admin\Members@index` |
| `/{language}/{class}/{function}/{params...}` | `Web\{Class}` | `/en/home/index` → `Web\Home@index` |
| `/{class}/{function}/{params...}` | `Web\{Class}` | `/home/index` → `Web\Home@index` |

### 显式 Web 路由

| 方法 | 路径 | 控制器 | 说明 |
|------|------|--------|------|
| `POST` | `/stripe/webhook` | `StripeWebhookController@handle` | Stripe 支付回调(无需 CSRF) |
| `GET` | `/posts/details/{postId}` | `Web\Posts@details` | 帖子详情页 |
| `POST` | `/posts/{postId}/qa-ask` | `Web\Posts@qaAsk` | 帖子问答(AI 回复) |
| `ANY` | `/{segments?}` | `RouteMapping@index` | 通配路由(所有其他请求) |

### 前台页面路由表

| URL | 控制器 | 方法 | 页面说明 |
|-----|--------|------|---------|
| `/`, `/home` | `Web\Home` | `index` | 首页 |
| `/home/chat` | `Web\Home` | `chat` | AI 聊天接口(JSON) |
| `/home/qrcode` | `Web\Home` | `qrcode` | 二维码生成 |
| `/home/reset-xai-thread` | `Web\Home` | `resetXaiThread` | 重置对话线程 |
| `/account` | `Web\Account` | `index` | 个人中心 |
| `/account/profile` | `Web\Account` | `profile` | 个人资料编辑 |
| `/account/posts` | `Web\Account` | `posts` | 我的帖子 |
| `/account/posts_publish` | `Web\Account` | `posts_publish` | 发帖/编辑 |
| `/account_article` | `Web\Account_Article` | `index` | 我的文章 |
| `/account_article/comment` | `Web\Account_Article_Comment` | `index` | 文章评论 |
| `/account_login` | `Web\Account_Login` | `index` | 登录页 |
| `/account_registration` | `Web\Account_Registration` | `index` | 注册主页 |
| `/account_registration/individual` | `Web\Account_Registration_Individual` | `index` | 个人用户注册 |
| `/account_registration/migration_agent` | `Web\Account_Registration_Migration_Agent` | `index` | 移民代理注册 |
| `/account_registration/service_provider` | `Web\Account_Registration_Service_Provider` | `index` | 服务商注册 |
| `/account_registration/verification` | `Web\Account_Registration_Verification` | `index` | 邮箱验证 |
| `/account_registration/payment_done` | `Web\Account_Registration_Payment_Done` | `index` | 注册付款完成 |
| `/account_forgot` | `Web\Account_Forgot` | `index` | 忘记密码 |
| `/account_reset` | `Web\Account_Reset` | `index` | 重置密码 |
| `/account_logout` | `Web\Account_Logout` | `index` | 登出 |
| `/account_submission` | `Web\Account_Submission` | `index` | 签证提交 |
| `/account_submission/payment` | `Web\Account_Submission_Payment` | `index` | 签证付款 |
| `/account_submission/payment_done` | `Web\Account_Submission_Payment_Done` | `index` | 签证付款完成 |
| `/account_renew` | `Web\Account_Renew` | `index` | 账户续费 |
| `/account_renew/payment_done` | `Web\Account_Renew_Payment_Done` | `index` | 续费完成 |
| `/posts` | `Web\Posts` | `index` | 帖子列表 |
| `/posts/details/{id}` | `Web\Posts` | `details` | 帖子详情 |
| `/news` | `Web\News` | `index` | 新闻列表 |
| `/news/details/{id}` | `Web\News` | `details` | 新闻详情 |
| `/events` | `Web\Events` | `index` | 活动列表 |
| `/events/details/{id}` | `Web\Events` | `details` | 活动详情 |
| `/forum` | `Web\Forum` | `index` | 论坛列表 |
| `/forum/details/{id}` | `Web\Forum` | `details` | 论坛详情 |
| `/visa_options` | `Web\Visa_Options` | `index` | 签证选项列表 |
| `/visa_options/details/{id}` | `Web\Visa_Options` | `details` | 签证选项详情 |
| `/agents` | `Web\Agents` | `index` | 移民代理列表 |
| `/apply` | `Web\Apply` | `index` | 签证申请 |
| `/auto_fill` | `Web\Auto_Fill` | `index` | 自动填表 |
| `/free_assessment` | `Web\Free_Assessment` | `index` | 免费评估 |
| `/about_us` | `Web\About_Us` | `index` | 关于我们 |
| `/contact_us` | `Web\Contact_Us` | `index` | 联系我们 |
| `/faqs` | `Web\Faqs` | `index` | 常见问题 |
| `/terms` | `Web\Terms` | `index` | 服务条款 |
| `/privacy_statement` | `Web\Privacy_Statement` | `index` | 隐私声明 |
| `/data_deletion` | `Web\Data_Deletion` | `index` | 数据删除指引 |
| `/profile_comparison` | `Web\Profile_Comparison` | `index` | 方案对比 |
| `/service_provider_info` | `Web\Service_Provider_Info` | `index` | 服务商详情 |
| `/upgrade` | `Web\Upgrade` | `index` | 升级套餐 |
| `/pay_success` | `Web\Pay_Success` | `index` | 支付成功 |
| `/testchat` | `Web\Testchat` | `index` | 聊天测试页 |

### 后台页面路由表

| URL | 控制器 | 方法 | 页面说明 |
|-----|--------|------|---------|
| `/admin` | `Admin\Home` | `index` | 后台首页仪表盘 |
| `/admin/authn` | `Admin\Authn` | `index` | 登录页 |
| `/admin/authn/login` | `Admin\Authn` | `login` | 登录处理 |
| `/admin/authn/logout` | `Admin\Authn` | `logout` | 登出 |
| `/admin/authn/forgot` | `Admin\Authn` | `forgot` | 忘记密码 |
| `/admin/authn/reset` | `Admin\Authn` | `reset` | 重置密码 |
| `/admin/pages` | `Admin\Pages` | `index` | 通用页面管理 CRUD |
| `/admin/news` | `Admin\News` | `index` | 新闻管理 CRUD |
| `/admin/events` | `Admin\Events` | `index` | 活动管理 CRUD |
| `/admin/visa` | `Admin\Visa` | `index` | 签证选项管理 CRUD |
| `/admin/faqs` | `Admin\Faqs` | `index` | FAQ 管理 CRUD |
| `/admin/options/{type}` | `Admin\Options` | `index` | 选项管理(国家/组织类型等) |
| `/admin/plans/account` | `Admin\Plans` | `index` | 账户套餐管理 |
| `/admin/plans/visa_submission` | `Admin\Plans` | `index` | 签证提交方案管理 |
| `/admin/members` | `Admin\Members` | `index` | 会员管理(列表/编辑/禁用) |
| `/admin/posts` | `Admin\Posts` | `index` | 帖子审核管理 |
| `/admin/forum` | `Admin\Forum` | `index` | 论坛管理 |
| `/admin/media_files` | `Admin\Media_Files` | `index` | 媒体文件管理 |
| `/admin/privilege/role` | `Admin\Privilege` | `index` | 角色管理 |
| `/admin/privilege/user` | `Admin\Privilege` | `index` | 用户管理 |
| `/admin/setting/general` | `Admin\Setting` | `index` | 通用设置 |
| `/admin/setting/email` | `Admin\Setting` | `index` | 邮件设置 |
| `/admin/setting/whitelist` | `Admin\Setting` | `index` | IP白名单 |
| `/admin/profile` | `Admin\Profile` | `index` | 个人资料 |

## REST API 接口

**基础路径**: `/api/`
**认证方式**: Laravel Sanctum Bearer Token
**请求格式**: JSON / multipart/form-data
**响应格式**: JSON

### 文档管理

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| `POST` | `/api/documents/upload` | 是 | 上传文档 |
| `GET` | `/api/documents` | 是 | 文档列表 |
| `GET` | `/api/documents/{id}` | 是 | 查看文档详情 |
| `DELETE` | `/api/documents/{id}` | 是 | 删除文档 |
| `POST` | `/api/documents/{id}/reanalyze` | 是 | 重新分析文档 |

**上传文档请求**:
```
POST /api/documents/upload
Content-Type: multipart/form-data

file: (binary)
```

**文档详情响应**:
```json
{
  "id": 1,
  "member_id": 123,
  "original_filename": "visa-guide.pdf",
  "file_type": "application/pdf",
  "file_size": 2048000,
  "status": "completed",
  "extracted_text": "...",
  "analysis_result": {
    "summary": "...",
    "key_points": ["..."]
  }
}
```

### 课程申请

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| `POST` | `/api/course-applications` | 是 | 创建课程申请 |
| `GET` | `/api/course-applications/latest` | 是 | 获取最新申请 |

**创建申请请求**:
```json
{
  "family_name": "Chen",
  "given_name": "Xiao Ming",
  "email": "xmchen@example.com",
  "mobile": "+85212345678",
  "address": "...",
  "dob": "2000-01-01",
  "nationality": "CN",
  "education": "Bachelor",
  "english_tests": {"ielts": 7.0},
  "target_institution": "University of Melbourne",
  "target_program": "Master of IT",
  "start_year": 2026,
  "scholarship": false
}
```

### 用户信息

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| `GET` | `/api/user` | 是 | 获取当前认证用户信息 |

## Stripe Webhook

| 方法 | 路径 | 说明 |
|------|------|------|
| `POST` | `/stripe/webhook` | Stripe 事件回调(无需 CSRF 验证) |

支持的 Stripe 事件：
- `checkout.session.completed` - 支付完成
- `checkout.session.expired` - 支付过期
- `invoice.payment_succeeded` - 发票付款成功
- `invoice.payment_failed` - 发票付款失败
- `customer.subscription.updated` - 订阅更新
- `customer.subscription.deleted` - 订阅取消

## 认证方式

### 前台 (Member)

登录后系统在 Cookie 中设置 `member_token`，后续请求通过 Cookie 携带：
```
Cookie: member_token=<token_value>
```

### 后台 (User)

登录后系统在 Session 中存储 `user_token`，通过 `AdminAuthn` 中间件校验：
- Token 验证
- IP 白名单检查
- 角色权限验证

### API

使用 Laravel Sanctum，通过 Bearer Token 认证：
```
Authorization: Bearer <sanctum_token>
```

## 多语言参数

前台页面支持通过 URL 前缀切换语言：
- `/en/home` - 英语(默认)
- `/zh-hant/home` - 繁体中文
- `/zh-hans/home` - 简体中文
