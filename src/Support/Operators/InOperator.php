<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;
use Mitoop\LaravelQueryBuilder\Support\ValueHelper;

class InOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $values = ValueHelper::listValues($value);

        if ($values === []) {
            return;
        }

        $builder->{"{$whereType}In"}($field, $values);
    }
}
