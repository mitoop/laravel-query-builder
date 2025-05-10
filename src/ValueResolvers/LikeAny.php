<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;

class LikeAny implements ValueResolver
{
    public function __construct(protected array $fields) {}

    public function resolve($value): array
    {
        return [$this->fields, "%$value%"];
    }
}
