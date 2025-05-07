<?php

namespace Mitoop\LaravelQueryBuilder\Resolvers;

use Closure;
use Illuminate\Database\Eloquent\Attributes\Scope as ScopeAttr;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\RuleResolverInterface;
use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;
use Mitoop\LaravelQueryBuilder\Scope;
use ReflectionMethod;

class RuleResolver implements RuleResolverInterface
{
    public function __construct(protected Builder $builder, protected array $definition, protected array $input) {}

    public function resolve(): Builder
    {
        foreach ($this->definition as $key => $item) {
            if ($item instanceof Closure) {
                $this->builder->where($item);
            } elseif ($item instanceof Expression) {
                $this->builder->whereRaw($item);
            } elseif ($item instanceof Scope) {
                $method = $item->scopeName;
                $model = $this->builder->getModel();
                $args = $item->getArgs();
                $scopeMethod = 'scope'.Str::title($method);

                $hasScopeMethod = false;
                if (method_exists($model, $method)) {
                    $hasScopeMethod = ! empty((new ReflectionMethod($model, $method))->getAttributes(ScopeAttr::class));
                }

                if (! method_exists($model, $scopeMethod) && ! $hasScopeMethod) {
                    throw new InvalidArgumentException('Invalid scope:'.$method);
                }

                $this->builder->{$method}(...$args);
            } else {
                $field = is_int($key) ? $item : $key;

                $pattern = '/^(?:([\w-]+):)?([\w.\-$]+)(?:\|([\w-]+))?$/';

                if (! preg_match($pattern, $field, $matches)) {
                    throw new InvalidArgumentException('Invalid field:'.$field);
                }

                $sourceField = $matches[1] ?? null;
                $internalField = $matches[2] ?? null;
                $operator = $matches[3] ?? null;

                if (empty($sourceField)) {
                    if (! preg_match('/(?:[\w-]+[.$])?([\w-]+)$/', $field, $subMatch)) {
                        throw new InvalidArgumentException('Invalid field format:'.$field);
                    }
                    $sourceField = $subMatch[1];
                }

                $value = Arr::get($this->input, $sourceField);
                if (! is_int($key)) {
                    $value = $item instanceof ValueResolver ? $item->resolve($value) : $item;
                }

                if (is_null($value) || $value === '' || $value === []) {
                    continue;
                }

                $mixType = 'and';
                $operatorAndValue = is_array($item) ? $item : [$operator ?: 'eq' => $value];
                if (str_contains($internalField, '$')) {
                    [$relation, $relationField] = explode('$', $internalField);
                    $this->builder->whereHas(Str::camel($relation), function ($builder) use (
                        $operatorAndValue,
                        $mixType,
                        $relationField
                    ) {
                        $this->makeComboQuery($builder, $relationField, $mixType, $operatorAndValue);
                    });
                } else {
                    $this->builder->where(function ($builder) use ($field, $mixType, $operatorAndValue) {
                        $this->makeComboQuery($builder, $field, $mixType, $operatorAndValue);
                    });
                }
            }
        }

        return $this->builder;
    }

    protected function makeComboQuery($builder, $field, $mixType, $operatorAndValue): void
    {
        $whereType = $mixType === 'and' ? 'where' : 'orWhere';

        foreach ($operatorAndValue as $operator => $value) {
            if ($operator === 'in') {
                if ((is_array($value) || $value instanceof Collection) && ! empty($value)) {
                    $builder->{"{$whereType}In"}($field, $value);
                }
            } elseif ($operator === 'not_in') {
                if (is_array($value) && ! empty($value)) {
                    $builder->{"{$whereType}NotIn"}($field, $value);
                }
            } elseif ($operator === 'is') {
                $method = $whereType.'Null';
                $builder->{$method}($field);
            } elseif (Str::snake($operator) === 'is_not') {
                $method = $whereType.'NotNull';
                $builder->{$method}($field);
            } else {
                $builder->{$whereType}($field, $this->convertOperator($operator), $value);
            }
        }
    }

    protected function convertOperator($operator): string
    {
        $operatorMap = [
            'eq' => '=',
            'ne' => '<>',
            'gt' => '>',
            'gte' => '>=',
            'ge' => '>=',
            'lt' => '<',
            'lte' => '<=',
            'le' => '<=',
        ];

        return $operatorMap[$operator] ?? $operator;
    }
}
