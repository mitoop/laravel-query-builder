<?php

namespace Mitoop\LaravelQueryBuilder;

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

    public function when(mixed $condition): static
    {
        $this->condition = (bool) (is_callable($condition) ? call_user_func($condition) : $condition);

        return $this;
    }

    public function shouldSkip(): bool
    {
        return ! $this->condition;
    }
}
