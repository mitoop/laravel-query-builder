<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operator;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OperatorManager extends Manager
{
    public function use(string $operator): OperatorInterface
    {
        return $this->driver($operator);
    }

    protected function createInDriver()
    {
        return new InOperator;
    }

    protected function createNotInDriver()
    {
        return new NotInOperator;
    }

    protected function createJsonContainsDriver()
    {
        return new JsonContainsOperator;
    }

    protected function createBetweenDriver()
    {
        return new BetweenOperator;
    }

    protected function createIsDriver()
    {
        return new IsNullOperator;
    }

    protected function createIsNotDriver()
    {
        return new IsNotNullOperator;
    }

    protected function createDriver($driver)
    {
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return new DefaultOperator($driver);
    }

    public function getDefaultDriver(): string
    {
        throw new InvalidArgumentException('This manager does not support a default driver. Please call `use()` with the desired driver name.');
    }
}
