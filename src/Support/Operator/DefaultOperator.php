<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Database\Eloquent\Builder;

class DefaultOperator implements OperatorInterface
{
    public function __construct(protected string $operator) {}

    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if ($this->invalidOperator($this->operator, $builder)) {
            $this->operator = 'eq';
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $builder->{$whereType}($field, $this->convertOperator($this->operator), $value);
    }

    protected function invalidOperator($operator, $builder)
    {
        return (function () use ($operator) {
            /** @var Builder $this */
            return $this->invalidOperator($operator);
        })->call($builder);

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
            'like' => 'LIKE',
        ][$operator] ?? $operator;
    }
}
