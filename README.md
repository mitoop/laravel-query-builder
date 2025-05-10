# Laravel Query Builder

Laravel Query Builder 是一个基于接口设计、具备高度可替换性与扩展性的渐进式搜索构建包

在大多数系统中，中后台都是不可或缺的组成部分，而列表搜索功能则是最常见、最重复的开发场景之一。为此，我们构建了这个包：以接口为核心，提供清晰、可维护且强大的搜索逻辑构建能力，助你一步一步接近业务的真实需求。

通过将搜索逻辑从控制器中解耦，本包有效避免冗长的条件判断，使代码结构更清晰、职责更单一，也更易于维护与测试。无论是简单筛选还是复杂组合查询，都可以通过定义灵活的规则轻松实现，真正做到"一次封装，多处复用"。

本包的设计深受 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 启发，同时借鉴了 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) 的优秀实践。并在此基础上结合实际业务需求进行了增强与重构。

## 环境需求

- PHP >= 8.2
- Laravel ^11.0｜^12.0

## 安装

```shell
composer require mitoop/laravel-query-builder
```

## 快速使用
本包通过 Filter 类将所有搜索逻辑与控制器彻底解耦，支持高可维护性和强扩展性的查询构建。

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
所有搜索逻辑都集中在 `rules()` 方法中。我们为其设计了一套简洁直观的 DSL（领域特定语言），可用索引数组、关联数组混合定义，系统会自动识别并解析。
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
- `name`：未显式指定操作符，默认使用 `eq`（等于），查询 `name = ?`
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

你也可以扩展自定义操作符，在 `AppServiceProvider` 的 `boot` 方法中注册一个自定义操作符，操作符名称需满足仅包含 **小写字母、下划线（_）或中划线（-）** 的格式规范
```php
public function boot()
{
    app(OperatorManager::class)->extend('new_operator', fn($app) => new NewOperator);
}
```
`NewOperator` 类需要实现 `OperatorInterface` 接口，并定义具体的查询逻辑，例如：
```php
class NewOperator implements OperatorInterface
{
   public function apply(Builder $builder, string $whereType, string $field, $value): void
   {
       // $whereType 为 where 或者 orWhere
       // 自定义查询逻辑（whereIn）
       $builder->{"{$whereType}In"}($field, $value);
   }
}
```
### 值处理器：ValueResolver
将频繁出现的值处理逻辑提取为 `ValueResolver` 类，提升可复用性。例如你经常需要对某字段进行模糊匹配，可以将逻辑封装为：
```php
class Like implements Mitoop\LaravelQueryBuilder\Contracts\ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        return $this->prefix.$value.$this->suffix;
    }
}
```
使用方式：
```php
      protected function rules(): array
      {
          return [
              'email|like' => new Like, // 替代 $this->value('email', fn($email) => "%{$email}%")
          ];
      }
```
传入 `ValueResolver` 为 null 的值会被忽略，系统会自动跳过这条规则。例如，如果 `email` 的值为 `null`，会自动忽略这条规则。

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
            // 如果 keyword 值为 null，同样会自动忽略这条规则
            $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
        }),
    ];
}
```

### 排序：sorts 方法
排序字段默认从请求中的 `sorts` 字段提取，格式如：`sorts=-id,name`（降序 id，升序 name）。
- `allowedSorts`：限制允许排序的字段。
- 自定义字段名：通过 `SortResolver::sortFieldUsing()` 设定。
- 覆盖排序逻辑：通过重写 `sorts()` 方法完全控制排序：
```php
class UserFilter extends AbstractFilter
{
    // ...  
    protected function sorts(): array
    {
        return [
            'id' => 'desc', // 键值对形式
            'id desc', // 字符串形式
        ];
    }
}
```

## 高级特性
本包的查询与排序解析，分别由 `RuleResolverInterface` 与 `SortResolverInterface` 接口驱动。
你可以绑定自定义实现，覆盖默认行为，适配更复杂的查询需求。

此外，包内默认仅支持搜索与排序，但你也可以通过 `addResolver` 方法注册自定义解析器，扩展更多类型的查询逻辑。

⚠️ 建议：

该能力适用于具有 Laravel 包开发经验与接口编程能力的高级用户，使用前建议充分理解包的工作机制。

## 贡献

有什么新的想法和建议，欢迎提交 [issue](https://github.com/mitoop/laravel-query-builder/issues) 或者 [Pull Requests](https://github.com/mitoop/laravel-query-builder/pulls)。

## 协议

MIT


