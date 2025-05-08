# Laravel Query Builder

`Laravel Query Builder` 是一个基于接口设计、具有高可替换性和高扩展性的渐进式 Laravel 搜索构建包。

在几乎所有的系统中，中后台都是不可或缺的一部分，而中后台中的列表搜索功能又是最常见、最易重复开发的场景。为此，我们构建了这个搜索包: 以接口为核心，具备高度可替换性与可扩展性，旨在帮助开发者快速、清晰地实现各类复杂搜索逻辑。

通过将搜索规则从控制器中解耦出来，避免冗长的条件判断和逻辑分支，这个包能够让代码结构更清晰、职责更单一，同时更易于维护和测试。无论是简单筛选还是复杂组合查询，都可以通过灵活的规则定义来实现，真正做到“一次封装，多处复用”。

本包的设计深受 [zhuzhichao/laravel-advanced-search](https://github.com/matrix-lab/laravel-advanced-search) 的启发，同时也借鉴了 [spatie/laravel-query-builder](https://github.com/spatie/laravel-query-builder) 在代码组织和场景抽象方面的优秀实践。在此基础上，我们结合实际业务中的复杂搜索需求进行了增强与重构，旨在提供更灵活的规则定义、更清晰的职责划分，以及更高的可扩展性和可替换性。

## 环境需求

- PHP >= 8.2
- Laravel ^11.0｜^12.0

## 安装

```shell
composer require mitoop/laravel-query-builder
```

## 使用
在模型上（无论是静态调用还是实例调用）使用 filter 方法，并传入对应的 Filter 类即可。只需在 Filter 类中定义好相关的搜索规则，便能快速构建查询逻辑。

所有与搜索/排序相关的逻辑都集中定义在 Filter 类中，实现了与控制器的彻底解耦，结构清晰、职责单一，便于维护和扩展。

调用 filter 后，返回的依然是标准的 Laravel 查询构建器（Query Builder），因此你可以继续链式调用如 with、paginate、get 等方法，保留原有的使用习惯与灵活性。

此外，我们还提供了便捷的 Artisan 命令 php artisan make:filter，可快速生成符合规范的 Filter 类，提升开发效率。

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
为了清晰地描述搜索逻辑，我们为 rules 制定了一套轻量级的领域特定语言（DSL）。语法设计简单直观，不需要复杂的学习成本——接下来我们将一步步带你了解它的用法。


