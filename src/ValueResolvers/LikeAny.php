<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;

class LikeAny implements ValueResolver
{
    public function __construct(protected array $fields) {}

    public function resolve($value): array
    {
        $value = (string) $value;

        $value = preg_replace('/[^\P{C}\n]+/u', '', $value) ?? '';

        if ($value === '') {
            return [$this->fields, ''];
        }

        $escaped = addcslashes($value, '\%_');

        return [$this->fields, "%{$escaped}%"];
    }
}
