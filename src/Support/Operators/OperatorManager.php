<?php

namespace Mitoop\LaravelQueryBuilder\Support\Operators;

use Closure;
use Illuminate\Support\Manager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorInterface;

class OperatorManager extends Manager implements OperatorFactoryInterface
{
    public function use(string $operator): OperatorInterface
    {
        return $this->driver($this->normalize($operator));
    }

    public function register(string $name, Closure $callback): static
    {
        $key = $this->normalize($name);

        if (isset($this->customCreators[$key])) {
            throw new InvalidArgumentException("Operator [$key] already registered.");
        }

        return $this->extend($key, $callback);
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

    protected function createLikeDriver()
    {
        return new LikeOperator;
    }

    protected function createDriver($driver)
    {
        $driver = $this->normalize($driver);

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

    protected function normalize(string $name): string
    {
        return Str::snake($name);
    }
}
