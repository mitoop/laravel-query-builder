<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class CascadeOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        if (! is_array($value) || count($value) !== 2 || ! is_array($value[0] ?? null) || ! array_is_list($value[0]) || ! is_array($value[1] ?? null)) {
            throw new InvalidArgumentException('Cascade operator requires the Cascade value resolver.');
        }

        [$columns, $values] = $value;
        $pairs = [];

        foreach ($values as $index => $item) {
            if (! isset($columns[$index])) {
                break;
            }

            $pairs[] = [$columns[$index], $item];
        }

        if ($pairs === []) {
            return;
        }

        $builder->{$whereType}(function (Builder $query) use ($pairs): void {
            foreach ($pairs as [$column, $item]) {
                $query->where($column, $item);
            }
        });
    }
}
