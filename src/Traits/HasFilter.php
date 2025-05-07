<?php

namespace Mitoop\LaravelQueryBuilder\Traits;

use Mitoop\LaravelQueryBuilder\AbstractFilter;

trait HasFilter
{
    protected AbstractFilter $filter;

    public function withFilter(AbstractFilter $filter): static
    {
        $this->filter = $filter;

        return $this;
    }
}
