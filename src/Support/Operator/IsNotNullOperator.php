<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Database\Eloquent\Builder;

class IsNotNullOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $builder->{"{$whereType}NotNull"}($field);
    }
}
