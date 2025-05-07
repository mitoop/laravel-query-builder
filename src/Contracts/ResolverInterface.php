<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ResolverInterface
{
    public function __construct(Builder $builder, array $definition, array $input);

    public function resolve(): Builder;
}
