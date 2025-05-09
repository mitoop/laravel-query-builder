<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Database\Eloquent\Builder;

class DefaultOperator implements OperatorInterface
{
    public function __construct(protected string $operator) {}

    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if (is_array($value)) {
            $value = reset($value);
        }
        $builder->{$whereType}($field, $this->convertOperator($this->operator), $value);
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
