<?php

namespace Mitoop\LaravelQueryBuilder;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Mitoop\LaravelQueryBuilder\Contracts\BuilderAwareInterface;
use Mitoop\LaravelQueryBuilder\Contracts\FilterAwareInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorManagerAwareInterface;
use Mitoop\LaravelQueryBuilder\Contracts\ResolverInterface;
use Mitoop\LaravelQueryBuilder\Support\ValueHelper;
use Mitoop\LaravelQueryBuilder\Traits\HasBuilder;

abstract class AbstractFilter implements BuilderAwareInterface
{
    use HasBuilder;

    protected array $allowedSorts = ['id'];

    protected array $resolvers = [];

    protected array $data;

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function addResolver(string $name, string $parser): static
    {
        $this->resolvers[$name] = $parser;

        return $this;
    }

    abstract protected function rules(): array;

    protected function sorts(): array
    {
        return [];
    }

    protected function value($field, ?Closure $closure = null)
    {
        $value = Arr::get($this->data, $field);

        if (ValueHelper::isMeaningless($value)) {
            return null;
        }

        return $closure ? $closure($value) : $value;
    }

    protected function whenValue($value, Closure $callback, ?Closure $default = null): ?Closure
    {
        $value = $this->value($value);

        if (! is_null($value)) {
            return fn (BuilderInterface $q) => $callback($q, $value);
        } elseif ($default) {
            return fn (BuilderInterface $q) => $default($q);
        }

        return null;
    }

    public function __invoke(): Builder
    {
        foreach ($this->resolvers as $tag => $resolver) {
            if (method_exists($this, $tag)) {
                tap(app($resolver, ['builder' => $this->builder, 'definition' => $this->{$tag}(), 'input' => $this->data]),
                    function (ResolverInterface $resolver) {
                        if ($resolver instanceof FilterAwareInterface) {
                            $resolver->withFilter($this);
                        }

                        if ($resolver instanceof OperatorManagerAwareInterface) {
                            $resolver->withOperatorManager(app(OperatorFactoryInterface::class));
                        }

                        $resolver->resolve();
                    });
            }
        }

        return $this->builder;
    }
}
