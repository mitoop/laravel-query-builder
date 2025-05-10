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

所有与搜索/排序相关的逻辑都集中定义在 `Filter` 类中，实现了与控制器的彻底解耦，结构清晰、职责单一，便于维护和扩展。

调用 `filter` 后，返回的依然是标准的 Laravel 查询构建器（Query Builder），因此你可以继续链式调用如 with、paginate、get 等方法，保留原有的使用习惯与灵活性。

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

#### 定义搜索规则：rules 方法
所有搜索逻辑都通过 rules 方法集中定义。为了更清晰、统一地描述这些规则，我们为 rules 提供了一套简洁的领域特定语言（DSL）。语法直观，使用门槛低，便于快速上手。

在 rules 方法中，你可以同时使用 索引数组（Indexed Array） 和 关联数组（Associative Array） 的形式，两者可以混合存在，系统会自动进行统一解析。

```php
      protected function rules(): array
      {
          return [
              'name'
              'email|like' => $this->value('email', fn($email)=> "%{$email}%"),
          ];
      }
```
##### 规则解析
- 对于 name 字段，如果未显式指定操作符（操作符统一通过 | 分隔），系统默认使用 eq（等于）操作。也就是说，'name' 会被解析为 name = ? 的查询条件，字段对应的值则自动从前端传入的 name 请求参数中获取。
如果该参数在请求中未传入，系统会自动忽略这条规则，不会将其纳入查询条件中。

- 在 email 字段中，我们显式指定了操作符 like，因此系统会将其解析为 email LIKE ? 的查询条件。字段的值通过 `$this->value('email', fn($email) => "%{$email}%")` 获取：
  1. 第一个参数为请求中字段的名称（如 email）；
  2. 第二个参数是可选的闭包函数，用于对原始值进行处理，比如添加通配符 % 实现模糊查询。

值得注意的是：如果传入的参数值为空，value 方法会自动舍弃该规则，避免拼接无意义的查询条件。

另外，当请求参数的名称与数据库字段名称不一致时，你可以使用 请求参数名:字段名 的格式来进行映射。例如：`'name_alias:name'`
表示前端传入的参数是 name_alias，但实际查询的字段为 name，系统会自动解析并应用到查询中。这种写法非常适合字段命名不一致的场景，简洁直观。

类似地，对于 email 字段，也可以使用 `'email_alias:email|like'` 的写法，表示从请求中获取 email_alias 的值，应用到数据库字段 email 上，并使用 LIKE 操作。








