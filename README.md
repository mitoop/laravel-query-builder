# Laravel Query Builder

`Laravel Query Builder` 是一个基于接口设计、具有高可替换性和高扩展性的渐进式 Laravel 搜索构建包。

在几乎所有的系统中，中后台都是不可或缺的一部分，而中后台中的列表搜索功能又是最常见、最易重复开发的场景。为此，我们构建了这个搜索包：以接口为核心，具备良好的灵活性与可维护性，旨在帮助开发者快速、清晰地实现各类复杂搜索逻辑。

通过将搜索规则从控制器中解耦，避免冗长的条件判断和逻辑分支，这个包能够让代码结构更清晰、职责更单一，同时也更易于维护与测试。无论是简单筛选还是复杂组合查询，都可以通过灵活的规则定义轻松实现，真正做到“一次封装，多处复用”。

本包的设计深受 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 的启发，同时也借鉴了 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) 在代码组织和场景抽象方面的优秀实践。在此基础上，我们结合实际业务中的复杂搜索需求进行了增强与重构，进一步提升了其灵活性、扩展能力和替换能力，以适配更多业务场景。

## 环境需求

- PHP >= 8.2
- Laravel ^11.0｜^12.0

## 安装

```shell
composer require mitoop/laravel-query-builder
```

## 使用
在模型上（无论是静态调用还是实例调用）使用 `filter` 方法，并传入对应的 `Filter` 类即可。只需在 `Filter` 类中定义好相关的搜索规则，便能快速构建查询逻辑。

所有与搜索相关的逻辑都集中定义在 `Filter` 类中，实现了与控制器的彻底解耦，结构清晰、职责单一，便于维护和扩展。

调用 `filter` 后，返回的依然是标准的 Laravel 查询构建器（Eloquent Builder），因此你可以继续链式调用如 with、paginate、get 等方法，保留原有的使用习惯与灵活性。

此外，我们还提供了便捷的 Artisan 命令 `php artisan make:filter`，可快速生成符合规范的 `Filter` 类，提升开发效率。

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

### 定义搜索规则：rules 方法
所有搜索逻辑都通过 rules 方法集中定义。为了更清晰、统一地描述这些规则，我们为 rules 提供了一套简洁的领域特定语言（DSL）。语法直观，使用门槛低，便于快速上手。

在 rules 方法中，你可以同时使用索引数组和关联数组的形式，两者可以混合存在，系统会自动进行统一解析。

```php
      protected function rules(): array
      {
          return [
              'name'
              'email|like' => $this->value('email', fn($email)=> "%{$email}%"),
          ];
      }
```
### 规则解析
- 对于 name 字段，如果未显式指定操作符（操作符统一通过 | 分隔），系统默认使用 eq（等于）操作。也就是说，'name' 会被解析为 name = ? 的查询条件，字段对应的值则自动从前端传入的 name 请求参数中获取。
如果该参数在请求中未传入或者为空（空表示：null, '', []），系统会自动忽略这条规则，不会将其纳入查询条件中。

- 在 email 字段中，我们显式指定了操作符 like，因此系统会将其解析为 email LIKE ? 的查询条件。字段的值通过 `$this->value('email', fn($email) => "%{$email}%")` 获取：
  1. 第一个参数为请求中字段的名称（如 email）；
  2. 第二个参数是可选的闭包函数，用于对原始值进行处理，比如添加通配符 % 实现模糊查询。

值得注意的是：如果传入的参数值为空，value 方法会自动忽略这条规则，避免拼接无意义的查询条件。

另外，当请求参数的名称与数据库字段名称不一致时，你可以使用 请求参数名:字段名 的格式来进行映射。例如：`'name_alias:name'`
表示前端传入的参数是 name_alias，但实际查询的字段为 name，系统会自动解析并应用到查询中。这种写法非常适合字段命名不一致的场景，简洁直观。

类似地，对于 email 字段，也可以使用 `'email_alias:email|like'` 的写法，表示从请求中获取 email_alias 的值，应用到数据库字段 email 上，并使用 LIKE 操作。

如你所见，规则定义非常直观，采用 **<前端字段>:<数据库字段>|<操作符>** 的格式。

### 数据库字段
- 基础字段：直接映射常规数据库字段，如 name。
- JSON 字段(->)：支持查询 JSON 类型字段，如 profile->name。当前使用JSON 字段时，需指定前端字段名，如 `profile_name:profile->name`。
- 别名字段(.)：支持联表查询时使用表别名字段，如 u.name。
- 关联字段($)：支持通过关系查询字段，如 position$name，处理关联数据。

### 支持的操作符
默认支持以下操作符：
- eq：等于
- ne：不等于
- gt：大于
- lt：小于
- gte：大于等于
- lte：小于等于
- like：模糊查询
- in：包含
- not_in：不包含
- between：范围查询
- is_null：判断字段值是否为 NULL。仅当传入值等效为 true 时，才会应用该条件，筛选字段值为 NULL 的数据；否则忽略此规则。
- not_null：判断字段值是否不为 NULL。仅当传入值等效为 true 时，才会应用该条件，筛选字段值不为 NULL 的数据；否则忽略此规则。
- json_contains：检查 JSON 字段是否包含指定值

此外，你还可以自定义扩展操作符，但操作符名称需满足仅包含 **小写字母、下划线（_）或中划线（-）** 的格式规范。

例如，你可以在 ServiceProvider 的 boot 方法中注册一个自定义操作符：
```php
public function boot()
{
    app(OperatorManager::class)->extend('new_operator', function ($app) {
        return new NewOperator();
    });
}
```
NewOperator 类需要实现 OperatorInterface 接口，并定义具体的查询逻辑，例如：
```php
class NewOperator implements OperatorInterface
{
   public function apply(Builder $builder, string $whereType, string $field, $value): void
   {
       // $whereType 为 where 或者 orWhere
       // 自定义查询逻辑
       $builder->{"{$whereType}In"}($field, $value);
   }
}
```

### 使用 `ValueResolver` 实现复用
在实际业务中，一些字段的查询规则会频繁出现，例如：
```php
$this->value('email', fn($email) => "%{$email}%")
```
为了避免重复书写、提升可读性与复用性，你可以将其封装为一个独立的 `ValueResolver` 类：
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
然后在 `Filter` 中使用：
```php
      protected function rules(): array
      {
          return [
              'email|like' => new Like,
          ];
      }
```
同样的，如果 email 的值为空，系统会自动忽略这条规则。 Like 类只是一个简单的示例，你可以根据实际需求实现更复杂的逻辑。

### 进阶支持
rules 方法内还支持以下功能：
- RAW：直接使用原始 SQL 查询。
- 闭包：支持通过闭包实现自定义查询逻辑。
- Scope（本地作用域）：支持调用模型的本地作用域进行查询，既支持以 scope 开头的本地作用域，也支持通过 #[Scope] 注解定义的本地作用域。
- 关键词查询：提供快捷的关键词查询方法，简化常见查询的使用。

```php
protected function rules(): array
{
    return [
        DB::raw('name = 1'),
        
        // 使用闭包进行自定义查询
        function (Builder $builder) {
            $builder->where('name', 'like', '%mitoop%');
        },

        // 调用本地作用域（支持 scope 和 #[Scope] 注解）
        new Scope('scopeName', 'arg1', 'arg2'),

        // 关键词查询，简化多字段模糊查询
        // 如果 keyword 值为空，同样会自动忽略这条规则
        $this->whenValue('keyword', function(Builder $builder, $keyword) {
            $builder->whereAny(['name', 'email'], 'like', "%{$keyword}%");
        }),
    ];
}
```

### 排序
除了搜索，系统同样支持多字段排序。

默认情况下，会从前端请求中的 sorts 字段获取排序规则。例如：sorts=-id,name 表示按 id 降序、name 升序排序；多个字段可组合使用，- 用于表示降序。

为确保排序字段的安全性，你可以在 Filter 类中定义 $allowedSorts 属性，用于声明允许排序的字段列表。系统会自动校验前端传入的字段是否在该列表中，未被允许的字段将被忽略。

此外，你也可以通过在 ServiceProvider 的 boot 方法中调用：
```php
SortResolver::sortFieldUsing('your_custom_field');
```
自定义从前端哪个字段中提取排序规则。

如果你希望完全控制排序规则，可以在 Filter 类中定义 sorts 方法，手动指定排序逻辑。
此方式将覆盖默认的排序行为，并且不会受 $allowedSorts 限制。
```php
class UserFilter extends AbstractFilter
{
    protected function rules(): array
    {
        return [];
    }
    
    protected function sorts(): array
    {
        // 支持以下两种写法
        return [
            'id' => 'desc', // 键值对形式
            'id desc', // 字符串形式
        ];
    }
}
```





