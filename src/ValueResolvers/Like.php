<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;

class Like implements ValueResolver
{
    public function __construct(protected string $prefix = '%', protected string $suffix = '%') {}

    public function resolve($value): string
    {
        $value = (string) $value;

        $value = preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        $escaped = addcslashes($value, '\%_');

        return $this->prefix.$escaped.$this->suffix;
    }
}
