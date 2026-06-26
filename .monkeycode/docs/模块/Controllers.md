# 控制器层 (Controllers)

系统分为三层控制器：前台 Web 控制器(用户页面)、后台 Admin 控制器(管理页面)和 API 控制器(REST 接口)。

## 结构

```
app/Http/Controllers/
├── Controller.php                     # Laravel 基础控制器
├── CoreController.php (849行)         # 前台控制器基类(Session/视图/资源加载)
├── WebController.php (174行)          # 前台控制器基类(网站初始化/reCAPTCHA/Cookie)
├── AdminController.php (714行)        # 后台控制器基类(菜单/模板/权限)
├── RouteMapping.php (238行)           # 核心路由映射引擎
├── StripeWebhookController.php (592行) # Stripe Webhook 处理
├── Admin/                             # 后台控制器(16个)
│   ├── Authn.php                      # 后台认证
│   ├── Home.php                       # 仪表盘
│   ├── News.php                       # 新闻管理
│   ├── Events.php                     # 活动管理
│   ├── Visa.php                       # 签证选项管理
│   ├── Faqs.php                       # FAQ 管理
│   ├── Options.php                    # 选项管理(国家/类型等)
│   ├── Plans.php                      # 套餐计划管理
│   ├── Members.php                    # 会员管理
│   ├── Posts.php                      # 帖子审核
│   ├── Forum.php                      # 论坛管理
│   ├── Pages.php                      # 通用页面管理
│   ├── Media_Files.php                # 媒体文件管理
│   ├── Privilege.php                  # 权限管理(角色/用户)
│   ├── Profile.php                    # 个人资料
│   └── Setting.php                    # 系统设置
├── Api/                               # API 控制器(4个)
│   ├── ChatController.php             # 聊天日志 API
│   ├── DocumentController.php         # 文档上传/管理 API
│   ├── RagController.php              # RAG 检索(已禁用)
│   └── CourseApplicationController.php # 课程申请 API
└── Web/                               # 前台控制器(30个)
    ├── Home.php (1159+行)             # 首页 + AI 聊天核心
    ├── Account.php                    # 个人中心
    ├── Account_Login.php              # 登录
    ├── Account_Registration.php       # 注册主页
    ├── Account_Registration_Individual.php # 个人用户注册
    ├── Account_Registration_Migration_Agent.php # 移民代理注册
    ├── Account_Registration_Service_Provider.php # 服务商注册
    ├── Account_Registration_Verification.php # 邮箱验证
    ├── Account_Registration_Payment_Done.php # 注册付款完成
    ├── Account_Forgot.php             # 忘记密码
    ├── Account_Reset.php              # 重置密码
    ├── Account_Logout.php             # 登出
    ├── Account_Submission.php         # 签证提交
    ├── Account_Submission_Payment.php # 签证付款
    ├── Account_Submission_Payment_Done.php # 签证付款完成
    ├── Account_Renew.php              # 账户续费
    ├── Account_Renew_Payment_Done.php # 续费完成
    ├── Account_Article.php            # 用户文章
    ├── Account_Article_Comment.php    # 文章评论
    ├── Account_Posts.php              # 用户帖子
    ├── Account_Posts_Publish.php      # 发帖/编辑
    ├── Account_Profile.php            # 用户公开资料
    ├── Agents.php                     # 移民代理列表
    ├── Apply.php                      # 签证申请
    ├── Auto_Fill.php                  # 自动填表
    ├── Contact_Us.php                 # 联系我们
    ├── Data_Deletion.php              # 数据删除指引
    ├── Events.php                     # 活动列表
    ├── Events_Details.php             # 活动详情
    ├── Faqs.php                       # 常见问题
    ├── Forum.php                      # 论坛列表
    ├── Forum_Details.php              # 论坛详情
    ├── Free_Assessment.php            # 免费评估
    ├── News.php                       # 新闻列表
    ├── News_Details.php               # 新闻详情
    ├── Posts.php                      # 帖子列表
    ├── Posts_Details.php              # 帖子详情
    ├── Pay_Success.php                # 支付成功
    ├── Privacy_Statement.php          # 隐私声明
    ├── Profile_Comparison.php         # 方案对比
    ├── Service_Provider_Info.php      # 服务商详情
    ├── Terms.php                      # 服务条款
    ├── Testchat.php                   # 聊天测试
    ├── Upgrade.php                    # 升级套餐
    └── Visa_Options.php               # 签证选项列表
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `CoreController.php` | 前台基础控制器，提供 Session 管理、页面 Meta/导航加载、CSS/JS 自动加载、图片缓存生成、CSRF Token、Excel 导出等 |
| `AdminController.php` | 后台基础控制器，定义左侧菜单、模板列表/表单视图、CRUD 动作、权限验证、SendGrid 邮件 |
| `WebController.php` | 前台业务基类，网站初始化(国家列表/会员信息)、reCAPTCHA 验证、YouTube URL 解析、Cookie/访客 ID |
| `RouteMapping.php` | URL 路由映射，解析段 → 命名空间/类/方法，支持多语言和后台隔离 |
| `StripeWebhookController.php` | Stripe 事件回调，处理 checkout/invoice/subscription 事件 |
| `Web/Home.php` | 最大最核心的控制器，首页展示 + AI 聊天 + QR 码 + xAI 线程管理 |

## 依赖

**本模块依赖**:
- `app/Models/` - 数据模型(BaseModel 子类)
- `app/Services/` - 业务服务(ConversationFlowService 等)
- `app/Libraries/` - 第三方库(PayPal/SendGrid 等)
- `app/Http/Middleware/` - 中间件(认证/CSRF)

**依赖本模块的**:
- `routes/web.php` - 路由指向控制器
- `routes/api.php` - API 路由指向控制器
- `resources/views/` - 视图由控制器加载

## 规范

### 继承链
- 后台控制器 → `AdminController` → `Controller`
- 前台控制器(业务) → `WebController` → `CoreController` → `Controller`
- 前台控制器(简单) → `CoreController` → `Controller`
- API 控制器 → `Controller`

### 方法模式
- `index()` - 页面加载入口
- `list()` / `form()` / `save()` / `delete()` - CRUD 操作
- AJAX 方法直接在控制器中定义并通过 `$_POST['action']` 分发

### 添加新控制器
1. 在对应目录创建文件
2. 继承对应的基类
3. 实现 `index()` 方法
4. 访问 `/{class_name}` 即可(RouteMapping 自动映射)
