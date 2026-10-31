<?php

namespace Mitoop\LaravelQueryBuilder\Traits;

use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;

trait HasOperatorManager
{
    protected OperatorFactoryInterface $operatorFactory;

    public function withOperatorFactory(OperatorFactoryInterface $operatorFactory): static
    {
        $this->operatorFactory = $operatorFactory;

        return $this;
    }
}
