<?php

namespace Mitoop\LaravelQueryBuilder\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Support\Operators\OperatorInterface;

class LikeAnyOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        [$field, $value] = $value;

        $builder->{"{$whereType}Any"}($field, 'like', $value);
    }
}
