<?php

namespace Mitoop\LaravelQueryBuilder;

use Closure;

class Scope
{
    protected bool $condition = true;

    protected array $args;

    public function __construct(public string $scopeName, ...$args)
    {
        $this->args = $args;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function when(bool|Closure $condition): static
    {
        $this->condition = is_callable($condition) ? (bool) call_user_func($condition) : $condition;

        return $this;
    }

    public function shouldSkip(): bool
    {
        return ! $this->condition;
    }
}
