<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

interface OperatorFactoryInterface
{
    public function use(string $operator): OperatorInterface;
}
