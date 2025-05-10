<?php

namespace Mitoop\LaravelQueryBuilder\Traits;

use Mitoop\LaravelQueryBuilder\Support\Operators\OperatorManager;

trait HasOperatorManager
{
    protected OperatorManager $operatorManager;

    public function withOperatorManager(OperatorManager $operatorManager): static
    {
        $this->operatorManager = $operatorManager;

        return $this;
    }
}
