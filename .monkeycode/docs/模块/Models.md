# 数据模型 (Models)

系统的数据访问层，使用自定义 BaseModel 替代 Eloquent ORM，通过 DB 门面直接操作数据库。

## 结构

```
app/Models/
├── BaseModel.php (852行)       # 基础模型(通用 CRUD/条件构建/分页/软删除)
├── Member.php (1148行)         # 会员模型(注册/登录/社交登录/资料/签证提交)
├── User.php (620行)            # 后台用户模型(管理员登录/角色/权限)
├── Setting.php (151行)         # 系统设置模型(按名称和语言读取/保存)
├── Posts.php (436行)           # 帖子模型(CRUD/点赞/评论/标签)
├── Pages.php                   # 通用页面模型(任意表 CRUD/多语言/SEO/媒体)
├── Chatlog.php                 # 聊天记录模型(按会员/日期查询历史)
├── Forum.php                   # 论坛模型(帖子/评论 CRUD)
├── Free_Assessment.php         # 免费评估表单模型
├── Media_Files.php             # 媒体文件模型(上传/类型过滤/排序/文件夹)
├── Chunk.php                   # RAG 文档切片模型
├── CourseApplication.php       # 课程申请模型
└── DocumentUpload.php          # 文档上传记录模型
```

## 关键文件

| 文件 | 目的 |
|------|------|
| `BaseModel.php` | 核心基类: `queryListData()`列表查询、`queryOneData()`单条查询、`queryInsertData()`插入、`queryUpdateData()`更新、`queryDeleteData()`软删除、`setWhere()`条件构建、`setOrder()`排序、分页 |
| `Member.php` | 最大的模型(1148行): 会员注册/登录/登出/Token 管理、Google/Facebook OAuth、密码重置、三种类型(个人/代理/服务商)的详细资料管理、签证提交管理 |
| `User.php` | 后台管理员模型: 登录/登出/Token、用户/角色 CRUD、权限验证 |
| `Posts.php` | 帖子完整功能: 发布/编辑、点赞/评论统计、标签、置顶/精华管理 |

## BaseModel 查询模式

```php
// 列表查询
$model = new Posts();
$model->setTable('table_name');
$model->setWhere('status', 'active');
$model->setWhere('created_at', '>', '2025-01-01');
$model->setOrder('id', 'desc');
$model->setPage(1);
$model->setPerPage(20);
$result = $model->queryListData();

// 单条查询
$post = $model->queryOneData($id);

// 插入
$newId = $model->queryInsertData([
    'title' => 'Title', 
    'content' => 'Content'
]);

// 更新
$model->queryUpdateData($id, ['status' => 'published']);

// 删除(软删除，设置 status 标记)
$model->queryDeleteData($id);
```

## 数据库表对应

| 模型 | 主表 | 关联表 |
|------|------|--------|
| `Member` | `member` | `member_token`, `member_details`, `member_agent`, `member_lawfirm`, `member_business_license` |
| `User` | `user` | `user_token`, `user_role` |
| `Posts` | `member_posts` | `member_posts_like`, `member_posts_comment` |
| `Pages` | 任意(按 type 区分) | `media_files` (附件关联) |
| `Chatlog` | `chat_log` | - |
| `Forum` | `forum` posts/comments | - |
| `Setting` | `setting` | - |
| `Media_Files` | `media_files` | - |
| `DocumentUpload` | `document_uploads` | - |
| `CourseApplication` | `course_applications` | - |
| `Chunk` | `chunks` | - |
| `Free_Assessment` | `free_assessment` | - |

## 依赖

**本模块依赖**:
- Laravel DB 门面 (`Illuminate\Support\Facades\DB`)
- `app/Support/` 工具类

**依赖本模块的**:
- `app/Http/Controllers/` - 所有控制器
- `app/Services/` - 业务服务层

## 规范

### 不使用 Eloquent 关系
所有表关联通过手动 JOIN 或多次查询实现，不定义 `belongsTo`/`hasMany` 等关系。

### 软删除
使用 `status` 字段而非 `deleted_at` 时间戳标记删除状态。

### 多语言字段
以 JSON 格式存储在单字段中: `{"en": "...", "zh-hant": "..."}`，查询时按语言解析。

### 添加新模型
1. 在 `app/Models/` 创建 PHP 文件
2. 继承 `BaseModel`
3. 实现业务特定的查询方法
4. 在 `BaseModel` 的通用方法基础上扩展
