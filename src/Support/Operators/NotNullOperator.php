<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class NotNullOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if ($value) {
            $builder->{"{$whereType}NotNull"}($field);
        }
    }
}
