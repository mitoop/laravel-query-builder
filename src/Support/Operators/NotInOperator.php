<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;
use Mitoop\LaravelQueryBuilder\Support\RuleParser;

class NotInOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $values = RuleParser::listValues($value);

        if ($values === []) {
            return;
        }

        $builder->{"{$whereType}NotIn"}($field, $values);
    }
}
