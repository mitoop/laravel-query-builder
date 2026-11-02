<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class LikeAnyOperator implements OperatorInterface
{
    public function apply(Builder $builder, string $whereType, string $field, $value): void
    {
        [$columns, $keyword] = $value;

        if ($keyword === '') {
            throw new InvalidArgumentException('Keyword cannot be empty.');
        }

        $like = new LikeOperator;

        $builder->{"{$whereType}"}(function (Builder $query) use ($columns, $keyword, $like) {
            foreach ($columns as $key => $column) {
                if (is_int($key)) {
                    $like->apply($query, 'orWhere', $column, $keyword);
                } elseif (is_array($column)) {
                    $relation = $key;
                    $query->orWhereHas($relation, function (Builder $q) use ($column, $keyword, $like) {
                        foreach ($column as $relField) {
                            $like->apply($q, 'orWhere', $relField, $keyword);
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
