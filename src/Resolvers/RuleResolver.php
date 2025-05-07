<?php

namespace Mitoop\LaravelQueryBuilder\Resolvers;

use Closure;
use Illuminate\Database\Eloquent\Attributes\Scope as ScopeAttr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\RuleResolverInterface;
use Mitoop\LaravelQueryBuilder\Scope;
use Mitoop\LaravelQueryBuilder\Support\ValueHelper;
use ReflectionMethod;

class RuleResolver implements RuleResolverInterface
{
    public function __construct(protected Builder $builder, protected array $definition, protected array $input) {}

    public function resolve(): Builder
    {
        foreach ($this->definition as $key => $item) {
            if ($item instanceof Closure) {
                $this->builder->where($item);

                continue;
            }

            if ($item instanceof Expression) {
                $this->builder->whereRaw($item);

                continue;
            }

            if ($item instanceof Scope) {
                $this->applyScope($item);

                continue;
            }

            $resolved = ValueHelper::resolveField($key, $item, $this->input);

            if (is_null($resolved)) {
                continue;
            }

            ['field' => $field, 'mix' => $mix, 'operator' => $operator] = $resolved;

            if (str_contains($field, '$')) {
                [$relation, $relationField] = explode('$', $field, 2);
                $this->builder->whereHas(Str::camel($relation), function (Builder $builder) use (
                    $operator,
                    $mix,
                    $relationField
                ) {
                    $this->makeComboQuery($builder, $relationField, $mix, $operator);
                });

                continue;
            }

            $this->builder->where(function (Builder $builder) use ($field, $mix, $operator) {
                $this->makeComboQuery($builder, $field, $mix, $operator);
            });
        }

        return $this->builder;
    }

    protected function applyScope(Scope $scope): void
    {
        $method = $scope->scopeName;
        $args = $scope->getArgs();
        $model = $this->builder->getModel();
        $scopeMethod = 'scope'.Str::title($method);

        $hasScopeAttr = method_exists($model, $method)
            && ! empty((new ReflectionMethod($model, $method))->getAttributes(ScopeAttr::class));

        if (! method_exists($model, $scopeMethod) && ! $hasScopeAttr) {
            throw new InvalidArgumentException("Invalid scope: $method");
        }

        $this->builder->{$method}(...$args);
    }

    protected function makeComboQuery(Builder $builder, $field, $mix, $operator): void
    {
        $whereType = $mix === 'and' ? 'where' : 'orWhere';

        foreach ($operator as $key => $value) {
            if ($key === 'in') {
                if ((is_array($value) || $value instanceof Collection) && ! empty($value)) {
                    $builder->{"{$whereType}In"}($field, $value);
                }
            } elseif ($key === 'not_in') {
                if (is_array($value) && ! empty($value)) {
                    $builder->{"{$whereType}NotIn"}($field, $value);
                }
            } elseif ($key === 'is') {
                $builder->{"{$whereType}Null"}($field);
            } elseif (Str::snake($key) === 'is_not') {
                $builder->{"{$whereType}NotNull"}($field);
            } else {
                $builder->{$whereType}($field, $this->convertOperator($key), $value);
            }
        }
    }

    protected function convertOperator(string $operator): string
    {
        return [
            'eq' => '=',
            'ne' => '<>',
            'gt' => '>',
            'gte' => '>=',
            'ge' => '>=',
            'lt' => '<',
            'lte' => '<=',
            'le' => '<=',
        ][$operator] ?? $operator;
    }
}
