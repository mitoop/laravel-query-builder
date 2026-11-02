<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;
use Mitoop\LaravelQueryBuilder\Support\LikeClause;

class Like implements ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        return LikeClause::pattern((string) $value, $this->prefix, $this->suffix);
    }
}
