<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Database\Eloquent\Builder;

class NotInOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if (is_array($value) && ! empty($value)) {
            $builder->{"{$whereType}NotIn"}($field, $value);
        }
    }
}
