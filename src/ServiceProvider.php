<?php

namespace Mitoop\LaravelQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Mitoop\LaravelQueryBuilder\Commands\MakeFilterCommand;
use Mitoop\LaravelQueryBuilder\Contracts\OperatorFactoryInterface;
use Mitoop\LaravelQueryBuilder\Contracts\RuleResolverInterface;
use Mitoop\LaravelQueryBuilder\Contracts\SortResolverInterface;
use Mitoop\LaravelQueryBuilder\Operators\LikeAnyOperator;
use Mitoop\LaravelQueryBuilder\Resolvers\RuleResolver;
use Mitoop\LaravelQueryBuilder\Resolvers\SortResolver;
use Mitoop\LaravelQueryBuilder\Support\Operators\OperatorManager;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public $bindings = [
        RuleResolverInterface::class => RuleResolver::class,
        SortResolverInterface::class => SortResolver::class,
    ];

    public $singletons = [
        OperatorFactoryInterface::class => OperatorManager::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFilterCommand::class,
            ]);
        }

        /**
         * @return \Illuminate\Database\Eloquent\Builder
         */
        Builder::macro('filter', function (string|array $filterClass, array $data = []) {
            $filter = is_array($filterClass)
                ? new class($filterClass) extends AbstractFilter
                {
                    public function __construct(protected array $rules) {}

                    public function rules(): array
                    {
                        return $this->rules;
                    }
                }
            : app($filterClass);

            if (! $filter instanceof AbstractFilter) {
                throw new InvalidArgumentException('Filter must be instance of AbstractFilter');
            }

            $filter->withBuilder($this)
                ->setData($data ?: request()->all())
                ->addResolver('rules', RuleResolverInterface::class)
                ->addResolver('sorts', SortResolverInterface::class);

            return $filter();
        });

        app(OperatorFactoryInterface::class)->register('like_any', fn () => new LikeAnyOperator);
    }
}
