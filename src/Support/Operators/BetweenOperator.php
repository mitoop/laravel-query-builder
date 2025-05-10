<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;

class BetweenOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if (is_array($value) && ! empty($value)) {
            $builder->{"{$whereType}Between"}($field, $value);
        }
    }
}
