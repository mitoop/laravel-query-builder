<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

interface OperatorFactoryAwareInterface
{
    public function withOperatorFactory(OperatorFactoryInterface $operatorFactory): static;
}
