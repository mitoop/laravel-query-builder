<?php

namespace Mitoop\LaravelQueryBuilder;

class Scope
{
    protected array $args;

    public function __construct(public string $scopeName, ...$args)
    {
        $this->args = $args;
    }

    public function getArgs(): array
    {
        return $this->args;
    }
}
