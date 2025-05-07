<?php

namespace Mitoop\LaravelQueryBuilder\Resolvers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Mitoop\LaravelQueryBuilder\Contracts\FilterAwareInterface;
use Mitoop\LaravelQueryBuilder\Contracts\SortResolverInterface;
use Mitoop\LaravelQueryBuilder\Traits\HasFilter;

class SortResolver implements FilterAwareInterface, SortResolverInterface
{
    use HasFilter;

    public function __construct(protected Builder $builder, protected array $definition, protected array $input) {}

    public function resolve(): Builder
    {
        foreach ($this->prepare() as $field => $direction) {
            if (is_int($field)) {
                $this->builder->orderByRaw($direction);
            } else {
                $this->builder->orderBy($field, $direction);
            }
        }

        return $this->builder;
    }

    protected function prepare(): array
    {
        if (! empty($this->definition)) {
            return $this->definition;
        }

        $allowedSorts = $this->getAllowedSorts();

        if (empty($allowedSorts)) {
            return [];
        }

        $sorts = Arr::get($this->input, 'sorts', []);

        $normalizedSorts = [];
        if ($sorts && is_string($sorts)) {
            $fields = explode(',', $sorts);

            foreach ($fields as $field) {
                $field = trim($field);

                if (str_starts_with($field, '-')) {
                    $field = substr($field, 1);
                    $direction = 'DESC';
                } else {
                    $direction = 'ASC';
                }

                if (! in_array($field, $allowedSorts, true)) {
                    continue;
                }

                $normalizedSorts[$field] = $direction;
            }
        }

        return $normalizedSorts;
    }

    protected function getAllowedSorts(): array
    {
        return (function () {
            return $this->allowedSorts;
        })->call($this->filter);
    }
}
