# 动态模板 (Dynamic Template)

后台管理页面的模板化生成机制，通过配置数组和通用视图模板，动态生成列表页和表单页。

## 什么是动态模板？

AI-mmi 后台的 CRUD 管理页面(新闻/活动/FAQ/签证选项等)大多数功能相似：数据列表展示、搜索筛选、新增编辑表单。为减少重复代码，`AdminController` 基类提供了 `templateListView()` 和 `templateFormView()` 方法，通过描述性的 PHP 配置数组自动渲染后台页面。

**关键特征**:
- 列表页: 搜索栏、排序、分页、批量操作，全部由配置数组驱动
- 表单页: 字段类型(text/image/file/select/date/richtext等)、验证、联动
- 通用 Blade 模板: `views/admin/template/list.blade.php` 和 `views/admin/template/form.blade.php`
- 一套配置即可生成完整 CRUD

## 代码位置

| 方面 | 位置 |
|------|------|
| 模板引擎 | `app/Http/Controllers/AdminController.php` (templateListView / templateFormView) |
| 列表模板 | `resources/views/admin/template/list.blade.php` |
| 表单模板 | `resources/views/admin/template/form.blade.php` |
| 后台 JS | `public/asset/js/admin/template_list.js` + `template_form.js` |
| 使用示例 | `app/Http/Controllers/Admin/News.php`, `Events.php`, `Faqs.php` 等 |

## 列表页配置

每个后台控制器通过定义 `$listConfig` 数组来配置列表页:

```php
// 示例: app/Http/Controllers/Admin/News.php
$this->listConfig = [
    'table'     => 'news',           // 数据表名
    'model'     => new Pages(),      // 模型实例
    'filters'   => [                 // 搜索过滤字段
        ['field' => 'title', 'type' => 'text', 'label' => '标题'],
        ['field' => 'created_at', 'type' => 'date_range', 'label' => '日期'],
    ],
    'columns'   => [                 // 列表列定义
        ['field' => 'title', 'label' => '标题', 'sortable' => true],
        ['field' => 'created_at', 'label' => '创建时间'],
        ['field' => 'status', 'label' => '状态', 'type' => 'status'],
    ],
    'actions'   => ['edit', 'delete'],      // 行操作按钮
    'batch_actions' => ['delete'],          // 批量操作
    'per_page'  => 20,                      // 每页条数
];
```

## 表单页配置

通过 `$formConfig` 数组定义新增/编辑表单:

```php
// 示例
$this->formConfig = [
    'table'     => 'news',
    'model'     => new Pages(),
    'fields'    => [
        ['name' => 'title', 'type' => 'text', 'label' => '标题',
         'required' => true, 'multilang' => true],
        ['name' => 'content', 'type' => 'richtext', 'label' => '内容'],
        ['name' => 'cover_image', 'type' => 'image', 'label' => '封面图',
         'upload_path' => 'news/'],
        ['name' => 'category', 'type' => 'select', 'label' => '分类',
         'options' => [1 => '移民', 2 => '留学']],
        ['name' => 'status', 'type' => 'switch', 'label' => '发布'],
    ],
];
```

### 支持的字段类型

| 类型 | 说明 |
|------|------|
| `text` | 单行文本输入 |
| `textarea` | 多行文本 |
| `richtext` | 富文本编辑器(TinyMCE) |
| `image` | 图片上传(支持多张) |
| `file` | 文件上传 |
| `select` | 下拉选择 |
| `date` | 日期选择器 |
| `date_range` | 日期范围 |
| `switch` | 开关(布尔值) |
| `number` | 数字输入 |
| `hidden` | 隐藏字段 |

### 多语言字段

设置 `'multilang' => true` 后，表单会自动为每种语言(en/zh-hant/zh-hans)生成独立的输入框，数据以 JSON 格式存储在数据库中:
```json
{"en": "English Title", "zh-hant": "繁體標題", "zh-hans": "简体标题"}
```

## 使用示例

```php
// 在控制器中
public function index()
{
    // 处理搜索/排序/分页/删除请求
    $action = $_POST['action'] ?? 'list';
    
    match ($action) {
        'list'   => $this->templateListView(),
        'form'   => $this->templateFormView(),
        'save'   => $this->templateFormSave(),
        'delete' => $this->templateDelete(),
    };
}
```

## 注意事项

1. 列表和表单的模型必须使用 `BaseModel` 或 `Pages` 模型(支持通用数据表操作)
2. 媒体文件通过 `Media_Files` 模型管理上传和引用
3. `templateFormSave()` 自动处理多语言字段的 JSON 编码
4. 模板自动加载对应的 CSS/JS 资源(`CoreController` 的自动加载机制)
5. 权限检查在模板方法内部通过 `AdminAuthn` 中间件的权限数据完成
