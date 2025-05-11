<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class OperatorManager extends Manager implements OperatorFactoryInterface
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

    protected function createIsNullDriver()
    {
        return new IsNullOperator;
    }

    protected function createNotNullDriver()
    {
        return new NotNullOperator;
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
        throw new InvalidArgumentException('Please call `use()` with the desired operator name.');
    }
}
