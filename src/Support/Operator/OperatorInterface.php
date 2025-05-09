<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Database\Eloquent\Builder;

interface OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void;
}
