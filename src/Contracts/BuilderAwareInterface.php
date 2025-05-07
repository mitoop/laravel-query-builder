<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface BuilderAwareInterface
{
    public function withBuilder(Builder $builder): static;
}
