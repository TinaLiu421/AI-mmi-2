# 数据库 (Database)

数据库迁移、种子数据和核心表结构。

## 结构

```
database/
├── factories/
│   └── UserFactory.php                 # 用户测试数据工厂
├── migrations/                         # 数据库迁移(17个文件)
│   ├── 2024_12_29_000001_add_chat_mode_to_chat_log_table.php
│   ├── 2025_01_30_000000_create_course_applications_table.php
│   ├── 2025_08_06_120000_add_parent_id_to_posts_comments_table.php
│   ├── 2025_09_28_023405_create_payments_table.php
│   ├── 2025_09_30_000001_create_plans_table.php
│   ├── 2025_09_30_000002_create_services_table.php
│   ├── 2025_09_30_000003_create_plan_entitlements_table.php
│   ├── 2025_09_30_000004_create_subscriptions_table.php
│   ├── 2025_09_30_005127_add_member_id_to_payments_table.php
│   ├── 2025_10_09_000000_add_member_id_to_app_free_assessment.php
│   ├── 2025_10_13_000000_create_chunks_table.php
│   ├── 2025_10_22_000000_create_document_uploads_table.php
│   ├── 2025_10_24_000001_add_business_registration_to_member_details_table.php
│   ├── 2025_10_24_000002_create_member_business_license_table.php
│   ├── 2025_10_29_015046_add_social_login_fields_to_member_table.php
│   ├── 2025_11_01_080526_add_guest_and_session_to_chat_log_table.php
│   └── ... (基础 Laravel migration)
├── seeders/
│   ├── DatabaseSeeder.php              # 数据库种子
│   └── PlanServiceSeeder.php           # 套餐/服务种子数据
└── .gitignore
```

## 核心表结构

### 用户相关

| 表名 | 说明 | 关键字段 |
|------|------|---------|
| `member` | 会员主表 | id, email, password, type(1个人/2代理/3服务商), alias_name, full_name, verified, social_provider, social_id, status |
| `member_token` | 会员 Token | type(1登录/2重置), member_id, value, expiry_at |
| `member_details` | 个人用户信息 | nationality, occupation, interest_visas, interest_topics |
| `app_member_details` | 扩展会员详情 | services_country, registered_business_country/name/number |
| `member_agent` | 移民代理详情 | company_name, reg_number, countries_serving |
| `member_lawfirm` | 服务商详情 | firm_name, service_type, license_number |
| `member_business_license` | 营业执照 | license_country, issuing_authority, registration_number, status |
| `user` | 后台管理员 | name, email, password, role_id, single_mode |
| `user_token` | 管理员 Token | type, user_id, value, expiry_at |
| `user_role` | 后台角色 | name, allowed(JSON 权限) |

### 内容相关

| 表名 | 说明 | 关键字段 |
|------|------|---------|
| `member_posts` | 会员帖子 | member_id, title, content, category_type(1新闻/2活动), category_lang |
| `member_posts_like` | 帖子点赞 | posts_id, member_id, status |
| `member_posts_comment` | 帖子评论 | posts_id, member_id, content, parent_id(AI回复链接) |
| `forum_posts` | 论坛帖子 | member_id, title, content, category |
| `forum_comments` / `posts_comments` | 论坛评论 | post_id, member_id, content, parent_id |

### 业务相关

| 表名 | 说明 | 关键字段 |
|------|------|---------|
| `chat_log` | 聊天记录 | member_id, guest_id(索引), session_id(索引), related_id, type(ask/reply), content, chat_mode |
| `free_assessment` | 免费评估 | member_id, contact_info, visa_interest, message |
| `course_applications` | 课程申请 | member_id, personal info, english_tests(JSON), document_paths(JSON), status, payment_status |
| `document_uploads` | 文档上传 | member_id, original_filename, file_hash, extracted_text, analysis_result(JSON), status |
| `chunks` | RAG 文档切片 | source_type, source_id, chunk_index, content, meta(JSON) |
| `media_files` | 媒体文件 | type(page/member/...), category, file_path, file_type, status |

### 支付相关

| 表名 | 说明 | 关键字段 |
|------|------|---------|
| `plans` | 套餐计划 | code(free/all_ai/hybrid/premium/vip), name, duration_months, price_usd, stripe_price_id |
| `services` | 服务项 | code, name, category(migration/education/support/payment), unit |
| `plan_entitlements` | 套餐权益 | plan_id(FK), service_id(FK), quota, period_days, price_override |
| `subscriptions` | 用户订阅 | member_id(FK), plan_id(FK), status, started_at, ends_at, stripe_customer_id, stripe_subscription_id |
| `payments` | 付款记录 | member_id, stripe_session_id, stripe_subscription_id, amount_total, currency, status, raw_payload(JSON) |

### 系统相关

| 表名 | 说明 | 关键字段 |
|------|------|---------|
| `setting` | 系统设置 | name, value, lang |
| `pages` | 通用页面 | type(page/country/visa_options等), title(多语言JSON), content, status |

## 种子数据

### PlanServiceSeeder

初始化五级套餐(plans)、服务项(services)和套餐权益(plan_entitlements):

```php
// 套餐示例
['code' => 'free', 'name' => 'Free', 'price_usd' => 0, 'duration_months' => null]
['code' => 'all_ai', 'name' => 'AI Smart Plan', 'price_usd' => 29.99, 'duration_months' => 1]
['code' => 'hybrid', 'name' => 'Hybrid Expert Plan', 'price_usd' => 99.99, 'duration_months' => 1]
['code' => 'premium', 'name' => 'Premium Confidence Plan', 'price_usd' => 299.99, 'duration_months' => 1]
['code' => 'vip', 'name' => 'VIP Global Partner Plan', 'price_usd' => 999.99, 'duration_months' => 1]

// 服务项示例
['code' => 'ai_migration_qna', 'name' => 'AI Migration Q&A', 'category' => 'migration']
['code' => 'ai_education_qna', 'name' => 'AI Education Q&A', 'category' => 'education']
['code' => 'document_analysis', 'name' => 'Document Analysis', 'category' => 'support']
```

## 迁移历史

| 日期 | 操作 | 说明 |
|------|------|------|
| 2024-12-29 | ALTER | chat_log 添加 chat_mode 字段 |
| 2025-01-30 | CREATE | course_applications 表 |
| 2025-08-06 | ALTER | posts_comments / member_posts_comment 添加 parent_id(AI回复) |
| 2025-09-28 | CREATE | payments 表 |
| 2025-09-30 | CREATE | plans / services / plan_entitlements / subscriptions 表(套餐体系) |
| 2025-10-09 | ALTER | free_assessment 添加 member_id |
| 2025-10-13 | CREATE | chunks 表(RAG) |
| 2025-10-22 | CREATE | document_uploads 表(文档上传) |
| 2025-10-24 | ALTER/CREATE | 营业执照相关字段和表 |
| 2025-10-29 | ALTER | member 添加 social_provider / social_id |
| 2025-11-01 | ALTER | chat_log 添加 guest_id / session_id |

## 注意事项

1. 基础 Laravel 表(member, user, setting, pages 等)的迁移文件在更早的时间点
2. 部分迁移包含 `down()` 方法用于回滚，但尚未测试
3. `raw_payload` 字段(JSON)存储 Stripe 完整事件数据用于审计
4. `file_hash`(MD5)在 member_id 范围内唯一，防止重复上传
5. 多语言字段以 JSON 格式存储，如 `{"en": "...", "zh-hant": "..."}`
6. 软删除使用 `status` 字段标记而非 `deleted_at` 时间戳
