<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;
use Mitoop\LaravelQueryBuilder\Support\LikeClause;

class LikeOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $value = (string) $value;

        if ($value === '') {
            throw new InvalidArgumentException('Keyword cannot be empty.');
        }

        LikeClause::apply($builder, $whereType, $field, $value);
    }
}
