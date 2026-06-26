# 前端资源 (Assets)

前端层包括 Blade 视图模板、JavaScript 脚本、CSS 样式和第三方前端库。

## 结构

```
resources/
├── views/                              # Blade 视图模板
│   ├── web/                            # 前台视图(43个)
│   │   ├── home.blade.php              # 首页(含聊天窗口)
│   │   ├── common.blade.php            # 公共布局
│   │   ├── account_login.blade.php     # 登录页
│   │   ├── account_registration*.blade.php  # 注册系列(5个)
│   │   ├── account_forgot.blade.php    # 忘记密码
│   │   ├── account_reset.blade.php     # 重置密码
│   │   ├── account_profile.blade.php   # 个人资料
│   │   ├── account_posts*.blade.php    # 帖子管理(2个)
│   │   ├── account_article*.blade.php  # 文章管理(2个)
│   │   ├── account_submission*.blade.php  # 签证提交(3个)
│   │   ├── account_renew*.blade.php    # 续费(2个)
│   │   ├── posts_details.blade.php     # 帖子详情
│   │   ├── news_details.blade.php      # 新闻详情
│   │   ├── events_details.blade.php    # 活动详情
│   │   ├── forum*.blade.php            # 论坛(2个)
│   │   ├── visa_options*.blade.php     # 签证选项(2个)
│   │   ├── agents.blade.php            # 移民代理列表
│   │   ├── apply.blade.php             # 签证申请
│   │   ├── auto_fill*.blade.php        # 自动填表(2个)
│   │   ├── free_assessment.blade.php   # 免费评估
│   │   ├── about_us.blade.php          # 关于我们
│   │   ├── contact_us.blade.php        # 联系我们
│   │   ├── faqs.blade.php              # 常见问题
│   │   ├── terms.blade.php             # 服务条款
│   │   ├── privacy_statement.blade.php # 隐私声明
│   │   ├── data_deletion.blade.php     # 数据删除指引
│   │   ├── service_provider_info.blade.php # 服务商详情
│   │   ├── profile_comparison.blade.php    # 方案对比
│   │   ├── upgrade.blade.php           # 升级套餐
│   │   └── pay_success.blade.php       # 支付成功
│   ├── admin/                          # 后台视图(14个)
│   │   ├── home.blade.php              # 首页仪表盘
│   │   ├── common.blade.php            # 公共布局
│   │   ├── media_files.blade.php       # 媒体管理
│   │   ├── authn/                      # 认证(3个: login/forgot/reset)
│   │   ├── member_area/                # 会员区(3个: forum/posts/form)
│   │   ├── privilege/                  # 权限(2个: role/user form)
│   │   ├── setting/                    # 设置(3个: general/email/whitelist)
│   │   └── template/                   # 通用模板(2个: list/form)
│   └── components/                     # 组件
│       └── welcome-message.blade.php   # 聊天欢迎消息
├── css/
│   └── app.css                         # 主样式(Laravel Mix 编译)
├── js/
│   ├── app.js                          # 主 JS 入口
│   └── bootstrap.js                    # Bootstrap JS(axios/lodash)
└── lang/                               # 多语言文件
    ├── en/                             # 英语
    │   ├── _web.php                    # 前台翻译
    │   ├── _admin.php                  # 后台翻译
    │   ├── _global.php                 # 全局翻译
    │   ├── _database.php               # 数据表翻译
    │   ├── auth.php                    # 认证翻译
    │   ├── pagination.php              # 分页翻译
    │   ├── passwords.php               # 密码翻译
    │   └── validation.php              # 验证翻译
    ├── zh-hans/                        # 简体中文(同上结构)
    └── zh-hant/                        # 繁体中文(同上结构)

public/
├── index.php                           # Laravel 入口
├── favicon.ico                         # 网站图标
├── robots.txt                          # 爬虫协议
├── mix-manifest.json                   # Mix 构建清单
├── css/app.css                         # 编译后 CSS
├── asset/
│   ├── lib/base/                       # jQuery + iWeb UI 框架
│   │   ├── jquery.min.js
│   │   ├── iweb.min.js
│   │   └── iweb.min.css
│   ├── lib/tinymce4/                   # TinyMCE 4 富文本编辑器
│   ├── lib/picker/                     # 日期/颜色选择器
│   ├── lib/slider/slick.min.js         # Slick 轮播图
│   ├── lib/3rd/                        # 第三方工具
│   │   ├── rtable.min.js               # 表格组件
│   │   ├── rcrop.min.js                # 图片裁剪
│   │   ├── jquery.s2t.js               # 简繁转换
│   │   └── avatar/                     # 头像裁剪(Hammer.js/IScroll/PhotoClip)
│   ├── js/web/                         # 前台 JS(22个)
│   │   ├── home.js
│   │   ├── account.js, account_login.js, account_registration.js
│   │   ├── account_forgot.js, account_reset.js
│   │   ├── account_submission.js, account_renew.js
│   │   ├── apply.js, auto_fill.js, contact_us.js, free_assessment.js
│   │   ├── posts.js, forum.js, faqs.js, visa_options.js
│   │   ├── about_us.js, welcome_message.js, document-upload.js, common.js
│   └── js/admin/                       # 后台 JS(10个)
│       ├── common.js, profile.js
│       ├── posts.js, forum.js, members.js, media_files.js
│       ├── template_list.js, template_form.js
│       ├── setting.js, authn.js, privilege.js
└── upload/                             # 用户上传文件
    ├── member_posts/                   # 帖子图片
    ├── member_avatar/                  # 用户头像(含 cache 子目录)
    └── member_logo/                    # 服务商 Logo
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `resources/views/web/home.blade.php` | 首页视图，包含 AI 聊天窗口、新闻/活动列表 |
| `resources/views/web/common.blade.php` | 前台公共布局(页头/页脚/导航/SEO Meta) |
| `resources/views/admin/template/list.blade.php` | 后台通用列表模板，数据表格+搜索+分页 |
| `resources/views/admin/template/form.blade.php` | 后台通用表单模板，字段渲染+验证+提交 |
| `public/asset/js/web/home.js` | 首页前端逻辑(AI 聊天交互/消息发送/UI 更新) |
| `public/asset/lib/base/iweb.min.js` | 自定义 UI 框架，提供组件/表单/弹窗等基础能力 |
| `resources/lang/en/_web.php` | 前台英语翻译，所有页面文本的键值映射 |

## 前端资源自动加载

`CoreController` 根据控制器类名和方法名自动加载对应的 CSS/JS 文件:

```php
// 访问 Web\Account_Login@index 时自动加载:
// CSS: public/asset/css/account_login.css (如果存在)
// JS:  public/asset/js/web/account_login.js (如果存在)
```

## 第三方前端库

| 库 | 版本 | 用途 |
|------|------|------|
| jQuery | 3.x | DOM 操作 |
| iWeb UI | 自定义 | UI 框架(组件/表单/弹窗/网格) |
| TinyMCE | v4 | 富文本编辑器(后台内容编辑) |
| Slick | - | 轮播图(首页) |
| DateTimePicker | - | 日期时间选择器 |
| Minicolors | - | 颜色选择器 |
| jQuery PhotoClip | - | 头像裁剪上传(搭配 Hammer.js + IScroll) |
| RCrop | - | 通用图片裁剪 |
| jquery.s2t.js | - | 简体/繁体中文实时转换 |
| RTable | - | 后台数据表格(排序/分页/搜索) |

## 编译流程

1. `resources/css/app.css` → PostCSS → `public/css/app.css`
2. `resources/js/app.js` → Webpack(Laravel Mix) → `public/js/app.js`
3. 构建命令: `npm run dev`(开发) / `npm run production`(生产)

## 注意事项

1. `public/asset/` 目录下的 JS/CSS 不受 Laravel Mix 管理，需手动维护
2. 第三方库放在 `public/asset/lib/`，不在 npm 中管理
3. 多语言文件使用 PHP 返回数组格式: `return ['key' => 'value'];`
4. Blade 视图中使用 `{{ lang('_web.key') }}` 调用翻译
5. 视图自动加载机制依赖文件命名与控制器方法名一致
