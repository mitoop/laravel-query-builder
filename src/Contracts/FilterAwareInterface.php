<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

use Mitoop\LaravelQueryBuilder\AbstractFilter;

interface FilterAwareInterface
{
    public function withFilter(AbstractFilter $filter): static;
}
