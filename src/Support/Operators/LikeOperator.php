<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class LikeOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $value = (string) $value;

        if ($value === '') {
            throw new InvalidArgumentException('Keyword cannot be empty.');
        }

        $builder->{$whereType}($field, 'like', $value);
    }
}
