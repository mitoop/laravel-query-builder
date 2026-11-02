<?php

namespace Mitoop\LaravelQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Support\LikeClause;
use Mitoop\LaravelQueryBuilder\Support\RuleParser;

class LikeColumn
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(
        public string $field,
        public string $type,
        public array $columns,
        public string $default,
    ) {}

    public function apply(Builder $builder, array $input): void
    {
        $keyword = $input[$this->field] ?? null;

        if (RuleParser::isMeaningless($keyword)) {
            return;
        }

        $pattern = LikeClause::pattern((string) $keyword);

        if ($pattern === '') {
            return;
        }

        $type = $input[$this->type] ?? null;
        $column = is_string($type) && in_array($type, $this->columns, true) ? $type : $this->default;

        LikeClause::where($builder, $column, $pattern);
    }
}
