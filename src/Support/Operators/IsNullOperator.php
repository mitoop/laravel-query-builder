<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class IsNullOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if ($value) {
            $builder->{"{$whereType}Null"}($field);
        }
    }
}
