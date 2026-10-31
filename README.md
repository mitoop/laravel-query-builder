# Laravel Query Builder

**Laravel Query Builder** 是一个基于接口设计、具备高扩展性的渐进式搜索构建包，旨在让 Laravel 中的复杂搜索变得**更简单、更清晰、更优雅**。

它将搜索逻辑从控制器中彻底解耦，使列表搜索功能的开发更加集中、可维护，同时支持高度复用和灵活扩展。`rules()` 里声明条件，调用后仍是原来的 Eloquent 查询，可以继续 `with()`、`paginate()`、`get()`。

本包的设计灵感来源于 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 和 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder)，并结合实际业务需求进行了增强与重构。

## 环境要求

* PHP ^8.2
* Laravel ^11.0 | ^12.0 | ^13.0

## 安装

```bash
composer require mitoop/laravel-query-builder
```

## 定义 Filter

```bash
php artisan make:filter UserFilter
```

命令会在 `App\Http\Filters` 下生成类：

```php
<?php

namespace App\Http\Filters;

use Mitoop\LaravelQueryBuilder\AbstractFilter;

class UserFilter extends AbstractFilter
{
    protected array $allowedSorts = ['id'];

    protected function rules(): array
    {
        return [];
    }
}
```

控制器里：

```php
use App\Http\Filters\UserFilter;
use App\Models\User;

$users = User::filter(UserFilter::class)->paginate();
```

不传第二个参数，或传入空数组时，使用当前请求的全部输入。要换成别的数据时传进去：

```php
User::filter(UserFilter::class, $request->validated())->paginate();
```

规则也可以直接写成数组，不必先建类：

```php
User::filter([
    'name',
    'status|in',
])->paginate();
```

值为 `null`、空字符串或空数组的规则会跳过。

## 搜索规则

规则写在 `rules()` 里。字符串的格式是：

```text
[请求字段]:[数据库字段]|[操作符]
```

冒号和竖线都可以省略。省略冒号时，请求字段和数据库字段同名。省略操作符时，用 `eq`。

```php
use Mitoop\LaravelQueryBuilder\ValueResolvers\DateRange;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;

protected function rules(): array
{
    return [
        'name',
        'email|like' => new Like,
        'created_from:created_at|gte',
        'created_to:created_at|lte',
        'created_at|between' => new DateRange,
        'nickname:profile->nickname|like' => new Like,
        'position$name|like' => new Like,
        'u.name',
    ];
}
```

| 写法 | 含义 |
| --- | --- |
| `name` | 请求里的 `name` 等于 `name` 列 |
| `email\|like` | 用 `like` 比较 `email` |
| `created_from:created_at\|gte` | 请求字段是 `created_from`，列是 `created_at` |
| `profile->nickname` | JSON 路径 |
| `position$name` | `position` 关联上的 `name` 列 |
| `u.name` | 带表别名的列 |

`position$name` 会变成 `whereHas('position', ...)`。关联名会转成驼峰，`user_profile$bio` 对应 `userProfile`。

## 操作符

| 操作符 | 作用 |
| --- | --- |
| `eq` | `=`，默认 |
| `ne` | `<>` |
| `gt` / `lt` | `>` / `<` |
| `gte` / `ge` | `>=` |
| `lte` / `le` | `<=` |
| `like` | `like`。通配符由值处理器加上，操作符本身不包 `%` |
| `in` / `not_in` | 数组，或 `1,2` 这种逗号分隔的字符串 |
| `between` | 正好两个值，数组或 `start,end`。数量不对会抛异常 |
| `is_null` / `not_null` | 请求值为真时才加上 `whereNull` / `whereNotNull` |
| `json_contains` | JSON 包含 |

数据库不认识的操作符会抛出 `InvalidArgumentException`。

自定义操作符在 `AppServiceProvider::boot()` 里注册：

```php
use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class BeforeOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $builder->{$whereType}($field, '<', $value);
    }
}

app(OperatorFactoryInterface::class)->register('before', fn () => new BeforeOperator);
```

注册名就是规则里的操作符，例如 `published_at|before`。

## 值处理

操作符只决定比较方式。`%`、日期边界这类转换交给值处理器。

* `Mitoop\LaravelQueryBuilder\ValueResolvers\Like`：两侧加上 `%`，并转义输入里的 `%`、`_` 和 `\`
* `Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny`：同一套转义，匹配多个列
* `Mitoop\LaravelQueryBuilder\ValueResolvers\DateRange`：把两个日期转成当天的开始和结束。格式不对时这条规则跳过

```php
use Mitoop\LaravelQueryBuilder\ValueResolvers\DateRange;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;
use Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny;

protected function rules(): array
{
    return [
        'email|like' => new Like,
        'keyword|like_any' => new LikeAny(['name', 'email']),
        'created_at|between' => new DateRange,
    ];
}
```

`Like` 的前后缀可以改，例如只匹配开头：`new Like('', '%')`。

字段有值时才追加一段查询，用 `whenValue()`：

```php
use Illuminate\Database\Eloquent\Builder;

$this->whenValue('keyword', function (Builder $builder, string $keyword): void {
    $builder->whereAny(['name', 'email'], 'like', '%'.$keyword.'%');
});
```

这里的 `%` 不会自动转义。用户输入里可能带有通配符时，用 `Like` 或 `LikeAny`。

自定义处理器实现 `Mitoop\LaravelQueryBuilder\Contracts\ValueResolver`，在 `resolve()` 里返回给操作符的值。

## 关联、Scope 和原生条件

`rules()` 里也可以放 Scope、闭包和 `DB::raw()`。

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Mitoop\LaravelQueryBuilder\Scope;

protected function rules(): array
{
    return [
        new Scope('active'),
        (new Scope('role', (int) $this->value('role_id')))->when((bool) $this->value('role_id')),
        function (Builder $builder): void {
            $builder->where('is_verified', true);
        },
        DB::raw('users.score > 100'),
    ];
}
```

`new Scope('emailVerified')` 和 `new Scope('email_verified')` 都会调用模型上的 `scopeEmailVerified`。`when()` 的条件不成立时，这个 Scope 不会加入查询。

Laravel 12 起，模型方法也可以用 `#[Scope]` 标记。Laravel 11 只有 `scopeXxx` 这种写法。

## 排序

请求参数默认是 `sorts`。字段前加 `-` 表示降序：

```text
sorts=-id,name
```

只有 `$allowedSorts` 里的字段会生效，其余忽略。默认只允许 `id`。

```php
protected array $allowedSorts = ['id', 'name', 'created_at'];
```

换参数名时改 `$sortField`。每个 Filter 用自己的名字：

```php
protected string $sortField = 'order_by';
```

`sorts()` 返回非空数组时，不再读取请求里的排序：

```php
protected function sorts(): array
{
    return [
        'id' => 'desc',
    ];
}
```

数字下标的值会原样写进 `orderByRaw()`，例如 `'created_at asc'`。

## 预加载

Filter 定义了 `with()` 时，查询构建前会自动预加载：

```php
protected function with(): array
{
    return ['org', 'roles'];
}
```

`with()` 需要是 `public` 或 `protected`。重写 `boot()` 时先调用父类，否则上面的预加载不会执行：

```php
public function boot(): void
{
    parent::boot();
}
```

## 默认筛选值

`booting()` 在请求数据写入之后、规则生效之前运行。请求里已经带了 `status` 时，下面这行不会覆盖它：

```php
public function booting(): void
{
    $this->data['status'] ??= 'active';
}
```

## 完整示例

```php
<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\AbstractFilter;
use Mitoop\LaravelQueryBuilder\Scope;
use Mitoop\LaravelQueryBuilder\ValueResolvers\DateRange;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;
use Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny;

class UserFilter extends AbstractFilter
{
    protected array $allowedSorts = ['id', 'created_at'];

    protected function rules(): array
    {
        return [
            'id',
            'name|like' => new Like,
            'email|like' => new Like,
            'status|in',
            'created_from:created_at|gte',
            'created_to:created_at|lte',
            'created_at|between' => new DateRange,
            'nickname:profile->nickname|like' => new Like,
            'tag:profile->tags|json_contains',
            'position$name|like' => new Like,
            'keyword|like_any' => new LikeAny(['name', 'email']),
            new Scope('active'),
            $this->whenValue('role_id', function (Builder $builder, int|string $roleId): void {
                $builder->where('role_id', (int) $roleId);
            }),
        ];
    }

    protected function with(): array
    {
        return ['position'];
    }
}
```

```php
User::filter(UserFilter::class)->paginate();
```

## 贡献

欢迎提交 [issue](https://github.com/mitoop/laravel-query-builder/issues) 或 [Pull Request](https://github.com/mitoop/laravel-query-builder/pulls)。

## 协议

MIT
