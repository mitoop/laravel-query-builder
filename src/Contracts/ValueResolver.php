<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

interface ValueResolver
{
    public function resolve($value): mixed;
}
