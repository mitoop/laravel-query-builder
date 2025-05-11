<?php

namespace Mitoop\LaravelQueryBuilder\Traits;

use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Support\Operators\OperatorManager;

trait HasOperatorManager
{
    protected OperatorManager $operatorFactory;

    public function withOperatorFactory(OperatorFactoryInterface $operatorFactory): static
    {
        $this->operatorFactory = $operatorFactory;

        return $this;
    }
}
