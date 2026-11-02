<?php

namespace Mitoop\LaravelQueryBuilder\Support;

use Illuminate\Database\Eloquent\Builder;

class LikeClause
{
    public static function pattern(string $value, string $prefix = '%', string $suffix = '%'): string
    {
        $value = preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        return $prefix.addcslashes($value, '\%_').$suffix;
    }

    public static function where(Builder $builder, string $column, string $pattern): void
    {
        self::apply($builder, 'where', $column, $pattern);
    }

    public static function orWhere(Builder $builder, string $column, string $pattern): void
    {
        self::apply($builder, 'orWhere', $column, $pattern);
    }

    public static function apply(Builder $builder, string $whereType, string $column, string $pattern): void
    {
        $sql = $builder->getQuery()->getGrammar()->wrap($column).' like ? escape ?';
        $boolean = $whereType === 'orWhere' ? 'or' : 'and';

        $builder->whereRaw($sql, [$pattern, '\\'], $boolean);
    }
}
