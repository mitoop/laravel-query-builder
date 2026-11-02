<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;
use Mitoop\LaravelQueryBuilder\Support\LikeClause;

class LikeAny implements ValueResolver
{
    public function __construct(protected array $fields) {}

    public function resolve($value): array
    {
        return [$this->fields, LikeClause::pattern((string) $value)];
    }
}
