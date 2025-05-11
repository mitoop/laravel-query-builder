# Laravel Query Builder
**Laravel Query Builder** 是一个基于接口设计、具备高扩展性的渐进式搜索构建包，旨在让 Laravel 中的复杂搜索更简单、更清晰、更优雅。

在大多数系统中，中后台都是不可或缺的组成部分，而列表搜索功能则是最常见、最重复的开发场景之一。为此，我们构建了这个包：以接口为核心，帮助你更简单、更轻松地构建可维护的搜索逻辑，实现逻辑复用，让重复的列表开发不再繁琐。

通过将搜索逻辑从控制器中解耦，本包有效避免冗长的条件判断，使代码结构更清晰、职责更单一，也更易于维护与测试。无论是简单筛选还是复杂组合查询，都可以通过定义灵活的规则轻松实现，真正做到"一次封装，多处复用"。

本包的设计深受 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 启发，同时借鉴了 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) 的优秀实践。并在此基础上结合实际业务需求进行了增强与重构。

无论你是正在构建中后台系统，还是希望将搜索逻辑从控制器中彻底解耦，这个包都能帮助你快速构建结构清晰、逻辑优雅的搜索系统。

## 环境需求

- PHP >= 8.2
- Laravel ^11.0|^12.0

## 安装

```shell
composer require mitoop/laravel-query-builder
```

## 快速使用
本包通过 `Filter` 类将所有搜索逻辑与控制器彻底解耦，支持高可维护性和强扩展性的查询构建。

在模型上调用 `filter` 方法并传入对应的 `Filter` 类，即可构建查询逻辑，实现与控制器的彻底解耦。
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
调用 `filter` 后返回的是原生 Eloquent 查询构建器，仍支持链式调用 `with`、`paginate`、`get` 等方法，保持熟悉的开发体验。

你也可以使用 Artisan 命令快速生成标准的 `Filter` 类：
```php
php artisan make:filter UserFilter
```

### 规则定义：rules 方法
```php
// 👎 传统写法(控制器中硬编码)
if ($request->filled('name')) {
    $query->where('name', $request->input('name'));
}
if ($request->filled('email')) {
    $query->where('email', 'like', '%'.$request->input('email').'%');
}

// 👍 DSL 写法(集中在 Filter 中)
protected function rules(): array
{
    return [
        'name',
        'email|like' => new Like,
    ];
}
```
👆 使用 DSL 后，搜索逻辑更加集中、简洁且易于维护。

所有搜索逻辑都集中在 `rules()` 方法中。我们为其设计了一套简洁直观的 DSL(领域特定语言)，可用索引数组、关联数组混合定义，系统会自动识别并解析。
```php
protected function rules(): array
{
    return [
        'name'
        'email|like' => $this->value('email', fn($email)=> "%{$email}%"),
    ];
}
```
### 规则解析示例
- `name`：未显式指定操作符，默认使用 `eq`(等于)，查询 `name = ?`
- `email|like`：使用 `like` 操作符，构建 `email LIKE ?` 条件，值通过闭包处理为模糊查询。

字段命名支持灵活映射：
```text
'name_alias:name' // 请求参数为 name_alias，实际查询 name 字段
'email_alias:email|like' // 请求参数 email_alias，查询 email 字段，使用 like 操作
```
字段规则完整格式为：**[前端字段名]:[数据库字段]|[操作符]**，其中冒号与竖线均为可选，用于字段映射与操作符指定。
### 字段类型支持
- 基础字段：直接映射常规数据库字段，如 `name`。
- JSON 字段(->)：如 `profile->name`，需指定前端字段名，如 `profile_name:profile->name`。
- 表别名字段(.)：如 `u.name`。
- 关联字段($)：如 `position$name`，用于关联查询。

### 支持的操作符
默认支持的操作符包括：`eq`, `ne`, `gt`, `lt`, `gte`, `lte`, `like`, `in`, `not_in`, `between`, `is_null`, `not_null`, `json_contains`

你也可以扩展自定义操作符，在 `AppServiceProvider` 的 `boot` 方法中注册一个自定义操作符，
操作符名称需满足仅包含 **小写字母、下划线(_)或中划线(-)** 的格式规范，这是为了确保规则 DSL 的解析稳定与一致性。
```php
public function boot()
{
    app(OperatorFactoryInterface::class)->register('new_operator', fn($app) => new NewOperator);
}
```
`NewOperator` 类需要实现 `OperatorInterface` 接口，并定义具体的查询逻辑，例如：
```php
class NewOperator implements OperatorInterface
{
   public function apply(Builder $builder, string $whereType, string $field, $value): void
   {
       // $whereType 为 where 或者 orWhere
       // 可自定义任意查询逻辑，这里仅示例 whereIn
        $builder->{"{$whereType}In"}($field, $value);
   }
}
```
你也可以参考内置的 `LikeAnyOperator` 实现方式，结合规则中的参数结构，实现灵活的字段控制与值包装能力，满足复杂查询需求。

### 值处理器：ValueResolver
在构建搜索规则时，经常会遇到同一类型的值需要重复进行相同的转换处理。为了避免重复编写匿名函数、提升规则的复用性和维护性，我们引入了 `ValueResolver` 接口。

你只需实现一个 `resolve` 方法，就可以将任何复杂的值转换逻辑封装成独立的类，灵活地应用在多个规则中，也方便项目中统一规范和扩展行为。

例如，模糊搜索逻辑可以封装为：
```php
class Like implements ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        return $this->prefix.$value.$this->suffix;
    }
}
```
又如，需要将传入的日期区间转换为完整的一天范围时：
```php
class DateRange implements ValueResolver
{
    public function resolve($value): ?array
    {
        if (! is_array($value) || count($value) !== 2) {
            return null;
        }

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
使用方式：
```php
protected function rules(): array
{
    return [
        'email|like' => new Like, // 替代 $this->value('email', fn($email) => "%{$email}%")
        'created_at|between' => new DateRange,
    ];
}
```
如果前端传入的某个字段值为 null 或者 []，该规则会被自动跳过，`ValueResolve` 不会被调用，确保只处理有效输入，避免无效查询。

### 进阶规则支持
- 原生 SQL：`DB::raw(...)`
- 闭包查询：直接传入 `Closure`
- 模型本地作用域：支持 `scopeXxx` 和 `#[Scope]` 注解
- 关键词搜索：`whenValue`
```php
protected function rules(): array
{
    return [
        DB::raw('name = 1'),
        function (Builder $builder) {
            $builder->where('name', 'like', '%mitoop%');
        },
        new Scope('scopeName', 'arg1', 'arg2'),
        $this->whenValue('keyword', function(Builder $builder, $keyword) {
            // 如果 keyword 为 null 或空字符串，将自动跳过，不执行此查询
            $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
        }),
    ];
}
```

### 排序：sorts 方法
在构建搜索逻辑时，排序(Sorting)通常是不可或缺的功能。该包默认通过请求参数中的 `sorts` 字段提取排序条件，例如：
```text
sorts=-id,name
```
上例表示按 `id` 倒序排序，再按 `name` 升序排序。
#### 默认行为
- 排序字段来源于请求参数 `sorts`，多个字段使用英文逗号分隔
- 字段前加 - 表示降序(desc)，否则为升序(asc)

#### 限制允许排序的字段
可以通过定义 `allowedSorts` 来限制允许被排序的字段，例如：
```php
protected array $allowedSorts = ['id', 'name', 'created_at'];
```
如果请求中包含不在 `allowedSorts` 列表内的字段，将被自动忽略。

#### 自定义排序参数字段名
如果你的请求中不是使用 `sorts` 字段作为排序参数，可以使用：
```php
SortResolver::sortFieldUsing('order_by');
```
这样会将排序字段提取逻辑从 `sorts` 切换为 `order_by`

#### 覆盖排序逻辑
如需完全控制排序逻辑，可通过重写 `sorts()` 方法实现：
```php
class UserFilter extends AbstractFilter
{
    protected function sorts(): array
    {
        return [
            'id' => 'desc', // 键值对写法，明确指定排序方向
            'created_at asc', // 字符串写法，也可混用
        ];
    }
}
```
你可以根据业务需求灵活返回排序字段和方向，该方法会跳过默认排序提取流程，完全由你自定义。

## 高级特性
#### 生命周期钩子：booting() 与 boot()
`booting()` 和 `boot()` 方法允许你在不同阶段扩展搜索构建逻辑。它们都是可选的，仅在需要时覆盖。

###### booting()
在执行构建逻辑之前调用，适合设置默认参数、预处理输入等。
```php
public function booting(): void
{
    // 例如设置默认参数
    $this->data['status'] ??= 'active';
}
```
###### boot()
在构建逻辑正式开始之前调用，适合动态注册过滤器或根据条件修改行为。
```php
public function boot(): void
{
    if (method_exists($this, 'with')) {
        $this->builder->with($this->with());
    }
}
```
#### 自定义类绑定和接口驱动
本包核心功能均以接口驱动设计，几乎所有实现都支持通过绑定自定义类进行替换，帮助你灵活适配更复杂或更个性化的业务场景。
该扩展能力主要面向具备 `Laravel` 包开发经验及接口编程能力的高级用户，建议在充分理解本包工作机制的基础上使用。

## 完整示例：UserFilter
以下是一个完整的 `UserFilter` 示例，展示了常见的搜索与排序组合写法：
```php
use Mitoop\LaravelQueryBuilder\Filters\AbstractFilter;
use Mitoop\LaravelQueryBuilder\Operators\Like;

class UserFilter extends AbstractFilter
{
    protected array $allowedSorts = ['id', 'created_at'];

    protected function rules(): array
    {
        return [
             // 精确匹配 ID
            'id',

            // 模糊搜索 name 和 email
            'name|like'  => new Like,
            'email|like' => new Like,

            // 枚举筛选(如启用状态：enabled, disabled)
            'status|in',

            // 时间范围过滤(created_at 字段)
            'created_from:created_at|gte',
            'created_to:created_at|lte',
            'created_at' => [
                'gte' => $this->value('created_at', fn($date) => Carbon::parse($date)),
                'lte' => $this->value('created_at', fn($date) => Carbon::parse($date)),
                'mix' => 'or' // 逻辑关系
            ],
            
            // 日期范围过滤(created_at 字段)
            'created_at|between' => new DateRange,
            
            // JSON 字段(nickname)
            'nickname:profile->nickname|like' => new Like,
            
             // JSON 数组字段：包含某个 tag
            'tag:profile->tags|json_contains',

            // 关联字段搜索(如职位名称 position.name)
            'position$name|like' => new Like,
            
            // 表别名字段(如在 join 中为 users 表取别名 u)
            'u.name',

            // 使用模型 Scope(如 scopeActive())
            new Scope('active'),

            // 使用闭包自定义条件(关键词匹配 name 或 email)
            $this->whenValue('keyword', function (Builder $builder, $keyword) {
                $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
            }),
            
            // Like Any 和上面闭包等效
            'keyword|like_any' => new LikeAny(['name', 'email'])

            // DB::raw(...)
            DB::raw('users.score > 100'),
            // 闭包 
            function (Builder $builder) {
                 $builder->where('is_verified', true);
            },
        ];
    }
}
```
在控制器中使用：
```php
$users = User::filter(UserFilter::class)->paginate();
```
## 贡献

有什么新的想法和建议，欢迎提交 [issue](https://github.com/mitoop/laravel-query-builder/issues) 或者 [Pull Requests](https://github.com/mitoop/laravel-query-builder/pulls)。

## 协议

MIT


