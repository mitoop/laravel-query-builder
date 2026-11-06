<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;
use Mitoop\LaravelQueryBuilder\Support\RuleParser;

class Cascade implements ValueResolver
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(protected array $columns)
    {
        if ($columns === [] || ! array_is_list($columns)) {
            throw new InvalidArgumentException('Cascade resolver requires a list of columns.');
        }

        foreach ($columns as $column) {
            if (! is_string($column) || $column === '') {
                throw new InvalidArgumentException('Cascade resolver columns must be non-empty strings.');
            }
        }
    }

    public function resolve($value): ?array
    {
        $values = RuleParser::listValues($value);

        if ($values === []) {
            return null;
        }

        return [$this->columns, $values];
    }
}
