<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class InOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if (is_array($value) && ! empty($value)) {
            $builder->{"{$whereType}In"}($field, $value);
        }
    }
}
