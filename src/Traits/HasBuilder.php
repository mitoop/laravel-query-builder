<?php

namespace Mitoop\LaravelQueryBuilder\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasBuilder
{
    protected Builder $builder;

    public function withBuilder(Builder $builder): static
    {
        $this->builder = $builder;

        return $this;
    }
}
