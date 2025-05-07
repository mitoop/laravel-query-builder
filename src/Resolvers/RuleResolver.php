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

            $field = is_int($key) ? $item : $key;

            if (! preg_match('/^(?:([\w-]+):)?([\w.\-$]+)(?:\|([\w-]+))?$/', $field, $matches)) {
                throw new InvalidArgumentException('Invalid field:'.$field);
            }

            $sourceField = $matches[1] ?? null;
            $internalField = $matches[2] ?? null;
            $operator = $matches[3] ?? 'eq';

            if (empty($sourceField)) {
                if (! preg_match('/(?:[\w-]+[.$])?([\w-]+)$/', $internalField, $subMatch)) {
                    throw new InvalidArgumentException('Invalid field format:'.$field);
                }
                $sourceField = $subMatch[1];
            }

            $value = Arr::get($this->input, $sourceField);

            if (! is_int($key)) {
                $isValueResolver = $item instanceof ValueResolver;
                $value = match (true) {
                    $isValueResolver && ! $this->isMeaninglessValue($value) => $item->resolve($value),
                    ! $isValueResolver => $item,
                    default => $value,
                };
            }

            if ($this->isMeaninglessValue($value)) {
                continue;
            }

            $operatorAndValue = is_array($item) ? $item : [$operator => $value];
            $mixType = strtolower(Arr::pull($operatorAndValue, 'mix', 'and'));

            if (str_contains($internalField, '$')) {
                [$relation, $relationField] = explode('$', $internalField, 2);
                $this->builder->whereHas(Str::camel($relation), function ($builder) use (
                    $operatorAndValue,
                    $mixType,
                    $relationField
                ) {
                    $this->makeComboQuery($builder, $relationField, $mixType, $operatorAndValue);
                });
            } else {
                $this->builder->where(function ($builder) use ($internalField, $mixType, $operatorAndValue) {
                    $this->makeComboQuery($builder, $internalField, $mixType, $operatorAndValue);
                });
            }
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

    protected function makeComboQuery($builder, $field, $mixType, $operatorAndValue): void
    {
        $whereType = $mixType === 'and' ? 'where' : 'orWhere';

        foreach ($operatorAndValue as $operator => $value) {
            if ($operator === 'in') {
                if ((is_array($value) || $value instanceof Collection) && ! empty($value)) {
                    $method = "{$whereType}In";
                    $builder->{$method}($field, $value);
                }
            } elseif ($operator === 'not_in') {
                if (is_array($value) && ! empty($value)) {
                    $method = "{$whereType}NotIn";
                    $builder->{$method}($field, $value);
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

    protected function isMeaninglessValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
