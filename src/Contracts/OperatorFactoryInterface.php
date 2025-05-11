<?php

namespace Mitoop\LaravelQueryBuilder\Contracts;

use Closure;

interface OperatorFactoryInterface
{
    public function use(string $operator): OperatorInterface;

    public function extend($driver, Closure $callback);
}
