<?php

namespace Mitoop\LaravelQueryBuilder\Operators;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class LikeAnyOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        [$field, $columns] = $value;

        $builder->{"{$whereType}"}(function (Builder $query) use ($columns, $field) {
            foreach ($columns as $key => $column) {
                if (is_int($key)) {
                    $query->orWhere($column, 'like', $field);
                } elseif (is_array($column)) {
                    $relation = $key;
                    $query->orWhereHas($relation, function (Builder $q) use ($column, $field) {
                        foreach ($column as $relField) {
                            $q->orWhere($relField, 'like', $field);
                        }
                    });
                } elseif ($column instanceof Closure) {
                    $relation = $key;
                    $query->orWhereHas($relation, $column);
                }
            }
        });
    }
}
