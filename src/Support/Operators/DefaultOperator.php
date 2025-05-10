<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;

class DefaultOperator implements OperatorInterface
{
    public function __construct(protected string $operator)
    {
        $this->operator = $this->convertOperator($this->operator);
    }

    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if ($this->invalidOperator($this->operator, $builder)) {
            $this->operator = 'eq';
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        $builder->{$whereType}($field, $this->operator, $value);
    }

    protected function invalidOperator($operator, Builder $builder): bool
    {
        $query = $builder->getQuery();

        return (function () use ($operator) {
            /** @var \Illuminate\Contracts\Database\Query\Builder $this */
            return $this->invalidOperator($operator);
        })->call($query);

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
            'like' => 'like',
        ][$operator] ?? $operator;
    }
}
