<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

use Mitoop\LaravelQueryBuilder\Support\Operators\OperatorManager;

interface OperatorManagerAwareInterface
{
    public function withOperatorManager(OperatorManager $operatorManager): static;
}
