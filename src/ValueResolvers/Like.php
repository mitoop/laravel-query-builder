<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;

class Like implements ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        return $this->prefix.$value.$this->suffix;
    }
}
