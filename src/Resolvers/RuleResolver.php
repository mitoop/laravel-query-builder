<?php

namespace Mitoop\LaravelQueryBuilder\Resolvers;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryAwareInterface;
use Mitoop\LaravelQueryBuilder\Contracts\RuleResolverInterface;
use Mitoop\LaravelQueryBuilder\LikeColumn;
use Mitoop\LaravelQueryBuilder\Scope;
use Mitoop\LaravelQueryBuilder\Support\RuleParser;
use Mitoop\LaravelQueryBuilder\Traits\HasOperatorFactory;
use ReflectionMethod;

class RuleResolver implements OperatorFactoryAwareInterface, RuleResolverInterface
{
    use HasOperatorFactory;

    public function __construct(protected Builder $builder, protected array $definition, protected array $input) {}

    public function resolve(): Builder
    {
        foreach ($this->definition as $key => $item) {
            if ($item instanceof Closure) {
                $this->builder->where($item);

                continue;
            }

            if ($item instanceof Expression) {
                $this->builder->whereRaw($item);

                continue;
            }

            if ($item instanceof Scope) {
                $this->applyScope($item);

                continue;
            }

            if ($item instanceof LikeColumn) {
                $item->apply($this->builder, $this->input);

                continue;
            }

            $resolved = RuleParser::resolveField($key, $item, $this->input);

            if (is_null($resolved)) {
                continue;
            }

            ['field' => $field, 'mix' => $mix, 'operator' => $operator] = $resolved;

            if (str_contains($field, '$')) {
                [$relation, $relationField] = explode('$', $field, 2);
                $this->builder->whereHas(Str::camel($relation), function (Builder $builder) use (
                    $operator,
                    $mix,
                    $relationField
                ) {
                    $this->makeComboQuery($builder, $relationField, $mix, $operator);
                });

                continue;
            }

            $this->builder->where(function (Builder $builder) use ($field, $mix, $operator) {
                $this->makeComboQuery($builder, $field, $mix, $operator);
            });
        }

        return $this->builder;
    }

    protected function applyScope(Scope $scope): void
    {
        if ($scope->shouldSkip()) {
            return;
        }

        $method = Str::camel($scope->scopeName);
        $args = $scope->getArgs();
        $model = $this->builder->getModel();
        $scopeMethod = 'scope'.ucfirst($method);

        if (! method_exists($model, $scopeMethod) && ! $this->hasScopeAttribute($model, $method)) {
            throw new InvalidArgumentException("Invalid scope: {$scope->scopeName}");
        }

        $this->builder->{$method}(...$args);
    }

    protected function hasScopeAttribute(object $model, string $method): bool
    {
        $attribute = 'Illuminate\Database\Eloquent\Attributes\Scope';

        if (! class_exists($attribute) || ! method_exists($model, $method)) {
            return false;
        }

        return (new ReflectionMethod($model, $method))->getAttributes($attribute) !== [];
    }

    protected function makeComboQuery(Builder $builder, $field, $mix, $operatorAndValue): void
    {
        $whereType = $mix === 'and' ? 'where' : 'orWhere';

        foreach ($operatorAndValue as $operator => $value) {
            $this->operatorFactory->use($operator)->apply($builder, $whereType, $field, $value);
        }
    }
}
