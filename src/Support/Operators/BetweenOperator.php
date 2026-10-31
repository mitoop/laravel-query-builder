<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;
use Mitoop\LaravelQueryBuilder\Support\RuleParser;

class BetweenOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        $values = RuleParser::listValues($value);

        if (count($values) !== 2) {
            throw new InvalidArgumentException('Between operator requires exactly two values.');
        }

        $builder->{"{$whereType}Between"}($field, $values);
    }
}
