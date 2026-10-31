<?php

namespace Mitoop\LaravelQueryBuilder\Tests;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\AbstractFilter;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;
use Mitoop\LaravelQueryBuilder\Scope;
use Mitoop\LaravelQueryBuilder\Tests\Fixtures\User;
use Mitoop\LaravelQueryBuilder\ValueResolvers\DateRange;
use Mitoop\LaravelQueryBuilder\ValueResolvers\Like;
use Mitoop\LaravelQueryBuilder\ValueResolvers\LikeAny;

class FilterTest extends PackageTestCase
{
    public function test_equals_filter_constrains_the_column(): void
    {
        $builder = User::query()->filter(['name'], ['name' => 'Ada']);

        $this->assertSame(['Ada'], $builder->getBindings());
        $this->assertStringContainsString('name', $builder->toSql());
    }

    public function test_unknown_operator_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator [eqq].');

        User::query()->filter(['name|eqq'], ['name' => 'Ada']);
    }

    public function test_comma_separated_values_match_an_in_filter(): void
    {
        $builder = User::query()->filter(['status|in'], ['status' => '0,1']);

        $this->assertSame(['0', '1'], $builder->getBindings());
    }

    public function test_array_values_match_an_in_filter(): void
    {
        $builder = User::query()->filter(['status|in'], ['status' => ['open', 'closed']]);

        $this->assertSame(['open', 'closed'], $builder->getBindings());
    }

    public function test_comma_separated_values_match_a_not_in_filter(): void
    {
        $builder = User::query()->filter(['status|not_in'], ['status' => '1, 2']);

        $this->assertSame(['1', '2'], $builder->getBindings());
        $this->assertStringContainsString('not in', $builder->toSql());
    }

    public function test_comma_separated_bounds_match_a_between_filter(): void
    {
        $builder = User::query()->filter(['created_at|between'], [
            'created_at' => '2024-01-01,2024-01-31',
        ]);

        $this->assertSame(['2024-01-01', '2024-01-31'], $builder->getBindings());
    }

    public function test_date_range_matches_the_full_days(): void
    {
        $builder = User::query()->filter([
            'created_at|between' => new DateRange,
        ], [
            'created_at' => ['2024-01-01', '2024-01-31'],
        ]);

        $bindings = array_map(strval(...), $builder->getBindings());

        $this->assertSame(['2024-01-01 00:00:00', '2024-01-31 23:59:59'], $bindings);
    }

    public function test_invalid_date_range_adds_no_constraint(): void
    {
        $builder = User::query()->filter([
            'created_at|between' => new DateRange,
        ], [
            'created_at' => '2024-01-01',
        ]);

        $this->assertSame([], $builder->getBindings());
        $this->assertStringNotContainsString('between', $builder->toSql());
    }

    public function test_between_filter_rejects_a_single_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Between operator requires exactly two values.');

        User::query()->filter(['created_at|between'], ['created_at' => '2024-01-01']);
    }

    public function test_camel_case_scope_name_calls_the_scope_method(): void
    {
        $builder = $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [new Scope('emailVerified')];
            }
        });

        $this->assertSame('NotNull', $builder->getQuery()->wheres[0]['type']);
        $this->assertSame('email_verified_at', $builder->getQuery()->wheres[0]['column']);
    }

    public function test_snake_case_scope_name_calls_the_camel_case_scope(): void
    {
        $builder = $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [new Scope('email_verified')];
            }
        });

        $this->assertSame('NotNull', $builder->getQuery()->wheres[0]['type']);
        $this->assertSame('email_verified_at', $builder->getQuery()->wheres[0]['column']);
    }

    public function test_skipped_scope_does_not_constrain_the_query(): void
    {
        $builder = $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [(new Scope('active'))->when(false)];
            }
        });

        $this->assertStringNotContainsString('active', $builder->toSql());
    }

    public function test_unknown_scope_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scope: missing');

        $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [new Scope('missing')];
            }
        });
    }

    public function test_attribute_scope_constrains_active_rows(): void
    {
        if (! class_exists(\Illuminate\Database\Eloquent\Attributes\Scope::class)) {
            $this->markTestSkipped('The #[Scope] attribute requires Laravel 12 or newer.');
        }

        $builder = $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [new Scope('active')];
            }
        }, model: \Mitoop\LaravelQueryBuilder\Tests\Fixtures\AttributeScopedUser::class);

        $this->assertSame([1], $builder->getBindings());
    }

    public function test_sort_ignores_fields_outside_the_allow_list(): void
    {
        $builder = User::query()->filter(['name'], [
            'name' => 'Ada',
            'sorts' => '-id,email',
        ]);

        $this->assertSame('id', $builder->getQuery()->orders[0]['column']);
        $this->assertSame('desc', $builder->getQuery()->orders[0]['direction']);
        $this->assertCount(1, $builder->getQuery()->orders);
    }

    public function test_each_filter_reads_its_own_sort_field(): void
    {
        $byName = $this->apply(new class extends AbstractFilter
        {
            protected string $sortField = 'order_by';

            protected array $allowedSorts = ['name'];

            protected function rules(): array
            {
                return [];
            }
        }, ['order_by' => '-name', 'sorts' => '-id']);

        $byId = $this->apply(new class extends AbstractFilter
        {
            protected array $allowedSorts = ['id'];

            protected function rules(): array
            {
                return [];
            }
        }, ['order_by' => '-name', 'sorts' => '-id']);

        $this->assertSame('name', $byName->getQuery()->orders[0]['column']);
        $this->assertSame('desc', $byName->getQuery()->orders[0]['direction']);
        $this->assertCount(1, $byName->getQuery()->orders);
        $this->assertSame('id', $byId->getQuery()->orders[0]['column']);
        $this->assertSame('desc', $byId->getQuery()->orders[0]['direction']);
        $this->assertCount(1, $byId->getQuery()->orders);
    }

    public function test_booting_fills_a_missing_filter_value(): void
    {
        $filter = new class extends AbstractFilter
        {
            public function booting(): void
            {
                $this->data['status'] ??= 'active';
            }

            protected function rules(): array
            {
                return ['status'];
            }
        };

        $this->assertSame(['active'], $this->apply($filter)->getBindings());
        $this->assertSame(['closed'], $this->apply($filter, ['status' => 'closed'])->getBindings());
    }

    public function test_with_method_eager_loads_relations(): void
    {
        $builder = $this->apply(new class extends AbstractFilter
        {
            protected function rules(): array
            {
                return [];
            }

            protected function with(): array
            {
                return ['profile'];
            }
        });

        $this->assertArrayHasKey('profile', $builder->getEagerLoads());
    }

    public function test_overridden_boot_still_eager_loads_when_it_calls_parent(): void
    {
        $filter = new class extends AbstractFilter
        {
            public bool $booted = false;

            public function boot(): void
            {
                parent::boot();

                $this->booted = true;
            }

            protected function rules(): array
            {
                return [];
            }

            protected function with(): array
            {
                return ['profile'];
            }
        };

        $builder = $this->apply($filter);

        $this->assertTrue($filter->booted);
        $this->assertArrayHasKey('profile', $builder->getEagerLoads());
    }

    public function test_like_escapes_wildcard_characters(): void
    {
        $builder = User::query()->filter([
            'email|like' => new Like,
        ], [
            'email' => '100%_a',
        ]);

        $this->assertSame(['%100\%\_a%'], $builder->getBindings());
    }

    public function test_like_any_escapes_wildcard_characters(): void
    {
        $builder = User::query()->filter([
            'keyword|like_any' => new LikeAny(['name', 'email']),
        ], [
            'keyword' => '100%_a',
        ]);

        $this->assertSame(['%100\%\_a%', '%100\%\_a%'], $builder->getBindings());
    }

    public function test_custom_operator_factory_is_used(): void
    {
        $this->app->instance(OperatorFactoryInterface::class, new class implements OperatorFactoryInterface
        {
            public function use(string $operator): OperatorInterface
            {
                return new class implements OperatorInterface
                {
                    public function apply(Builder $builder, string $whereType, string $field, $value): void
                    {
                        $builder->{$whereType}($field, '=', 'from-custom-factory');
                    }
                };
            }

            public function register(string $name, \Closure $callback): static
            {
                return $this;
            }
        });

        $builder = User::query()->filter(['name'], ['name' => 'Ada']);

        $this->assertSame(['from-custom-factory'], $builder->getBindings());
    }

    private function apply(AbstractFilter $filter, array $data = [], string $model = User::class): Builder
    {
        $this->app->instance($filter::class, $filter);

        return $model::query()->filter($filter::class, $data);
    }
}
