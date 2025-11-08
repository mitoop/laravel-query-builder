# Laravel Query Builder

**Laravel Query Builder** 是一个基于接口设计、具备高扩展性的渐进式搜索构建包，旨在让 Laravel 中的复杂搜索变得**更简单、更清晰、更优雅**。

它将搜索逻辑从控制器中彻底解耦，使列表搜索功能的开发更加集中、可维护，同时支持高度复用和灵活扩展。

本包的设计灵感来源于 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 和 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder)，并结合实际业务需求进行了增强与重构。

---

## 特性

* **DSL 搜索规则**：集中定义搜索逻辑，支持声明式、结构化、可复用的规则。
* **支持排序与分页**：可自定义排序字段，兼容 `paginate()`、`get()` 等链式调用。
* **值处理器(ValueResolver)**：统一处理输入值转换，避免重复闭包。
* **字段类型灵活**：支持 JSON 字段、关联字段、表别名字段。
* **高级扩展**：支持原生 SQL、闭包、自定义操作符、模型 Scope。
* **接口驱动**：几乎所有功能可通过自定义类替换，实现高度扩展。

---

## 环境需求

* PHP >= 8.2
* Laravel ^11.0 | ^12.0

---

## 安装

```bash
composer require mitoop/laravel-query-builder
```

---

## 快速上手

### 创建 Filter 类

Filter 类用于集中定义搜索逻辑，可通过 Artisan 快速生成：

```bash
php artisan make:filter UserFilter
```

```php
class UserFilter extends AbstractFilter
{
    protected array $allowedSorts = ['id'];

    protected function rules(): array
    {
        return [];
    }
}
```

调用示例：

```php
$users = User::filter(UserFilter::class)->paginate();
```

返回的是原生 Eloquent 查询构建器，仍支持链式调用 `with()`、`paginate()`、`get()` 等。

---

## DSL 搜索规则

### 传统写法（控制器硬编码）

```php
if ($request->filled('name')) {
    $query->where('name', $request->input('name'));
}
if ($request->filled('email')) {
    $query->where('email', 'like', '%'.$request->input('email').'%');
}
```

### 使用 DSL（集中在 Filter 中）

```php
protected function rules(): array
{
    return [
        'name',
        'email|like' => new Like,
    ];
}
```

### DSL 优势

* **集中管理**：所有规则在 `rules()` 中定义，控制器无需关心细节。
* **结构化**：清晰、易于阅读和维护。
* **高复用**：支持自定义操作符和值处理器，可在多个 Filter 中复用。
* **可扩展**：轻松支持 JSON、关联字段、模型 Scope 等复杂查询。

---

### 规则格式

```text
[前端字段名]:[数据库字段]|[操作符]
```

* 冒号用于前端字段与数据库字段映射（可选）
* 竖线用于指定操作符（可选）
* 支持索引数组、关联数组混合定义

示例：

```php
protected function rules(): array
{
    return [
        'name', // 默认 eq
        'email|like' => $this->value('email', fn($email)=> "%{$email}%"),
        'created_at|between' => new DateRange,
        'nickname:profile->nickname|like' => new Like,
        'position$name|like' => new Like,
    ];
}
```

---

### 支持的操作符

* 基础：`eq`, `ne`, `gt`, `lt`, `gte`, `lte`, `like`, `in`, `not_in`, `between`, `is_null`, `not_null`
* JSON：`json_contains`
* 自定义操作符：在 `AppServiceProvider` 注册

```php
app(OperatorFactoryInterface::class)->register('new_operator', fn($app) => new NewOperator);
```

---

### 值处理器 ValueResolver

用于统一处理输入值，例如模糊搜索或日期范围：

```php
class Like implements ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        return $this->prefix.$value.$this->suffix;
    }
}

class DateRange implements ValueResolver
{
    public function resolve($value): ?array
    {
        if (! is_array($value) || count($value) !== 2) return null;

        try {
            [$start, $end] = $value;
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
        } catch (Throwable) {
            return null;
        }

        return [$start, $end];
    }
}
```

使用示例：

```php
protected function rules(): array
{
    return [
        'email|like' => new Like,
        'created_at|between' => new DateRange,
    ];
}
```

> 如果前端传入值为 `null` 或空数组，该规则会自动跳过，避免无效查询。

---

### 高级规则支持

* **原生 SQL**：`DB::raw(...)`
* **闭包查询**：

```php
function (Builder $builder) {
    $builder->where('is_verified', true);
}
```

* **模型 Scope**：支持 `scopeXxx()` 或 `#[Scope]` 注解
* **关键词搜索**：

```php
$this->whenValue('keyword', function(Builder $builder, $keyword) {
    $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
});
```

---

### 排序：sorts 方法

默认通过请求参数 `sorts` 提取排序条件，例如：

```
sorts=-id,name
```

* 字段前加 `-` 表示降序，其他为升序
* 可通过 `$allowedSorts` 限制允许排序字段

```php
protected array $allowedSorts = ['id', 'name', 'created_at'];
```

* 自定义请求参数：

```php
SortResolver::sortFieldUsing('order_by');
```

* 完全自定义排序：

```php
protected function sorts(): array
{
    return [
        'id' => 'desc',
        'created_at asc',
    ];
}
```

---

### 生命周期钩子

* **booting()**：执行构建逻辑前调用，适合设置默认参数

```php
public function booting(): void
{
    $this->data['status'] ??= 'active';
}
```

* **boot()**：构建逻辑正式开始前调用，适合动态注册过滤器或修改行为

```php
public function boot(): void
{
    if (method_exists($this, 'with')) {
        $this->builder->with($this->with());
    }
}
```

---

### 完整示例：UserFilter

```php
use Mitoop\LaravelQueryBuilder\Filters\AbstractFilter;
use Mitoop\LaravelQueryBuilder\Operators\Like;

class UserFilter extends AbstractFilter
{
    protected array $allowedSorts = ['id', 'created_at'];

    protected function rules(): array
    {
        return [
            'id',
            'name|like'  => new Like,
            'email|like' => new Like,
            'status|in',
            'created_from:created_at|gte',
            'created_to:created_at|lte',
            'created_at|between' => new DateRange,
            'nickname:profile->nickname|like' => new Like,
            'tag:profile->tags|json_contains',
            'position$name|like' => new Like,
            'u.name',
            new Scope('active'),
            $this->whenValue('keyword', function (Builder $builder, $keyword) {
                $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
            }),
            'keyword|like_any' => new LikeAny(['name', 'email']),
            DB::raw('users.score > 100'),
            function (Builder $builder) {
                $builder->where('is_verified', true);
            },
        ];
    }
}
```

控制器使用：

```php
$users = User::filter(UserFilter::class)->paginate();
```

---

## 贡献

有什么新的想法和建议，欢迎提交 [issue](https://github.com/mitoop/laravel-query-builder/issues) 或 [Pull Requests](https://github.com/mitoop/laravel-query-builder/pulls)。

---

## 协议

MIT
