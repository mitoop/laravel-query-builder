<?php

namespace Mitoop\LaravelQueryBuilder\Operators;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class LikeAnyOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        [$keyword, $columns] = $value;

        $builder->{"{$whereType}"}(function (Builder $query) use ($columns, $keyword) {
            foreach ($columns as $key => $column) {
                if (is_int($key)) {
                    $query->orWhere($column, 'like', $keyword);
                } elseif (is_array($column)) {
                    $relation = $key;
                    $query->orWhereHas($relation, function (Builder $q) use ($column, $keyword) {
                        foreach ($column as $relField) {
                            $q->orWhere($relField, 'like', $keyword);
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
