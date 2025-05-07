<?php

namespace Mitoop\LaravelQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Mitoop\LaravelQueryBuilder\Commands\MakeFilterCommand;
use Mitoop\LaravelQueryBuilder\Contracts\RuleResolverInterface;
use Mitoop\LaravelQueryBuilder\Contracts\SortResolverInterface;
use Mitoop\LaravelQueryBuilder\Resolvers\RuleResolver;
use Mitoop\LaravelQueryBuilder\Resolvers\SortResolver;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public $bindings = [
        RuleResolverInterface::class => RuleResolver::class,
        SortResolverInterface::class => SortResolver::class,
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
        Builder::macro('filter', function (string $filterClass, array $data = []) {
            /** @var Builder $this */
            $filter = tap(app($filterClass), fn (AbstractFilter $filter) => $filter->withBuilder($this));

            /** @var AbstractFilter $filter */
            $filter->setData($data ?: request()->all());
            $filter->addResolver('rules', RuleResolverInterface::class);
            $filter->addResolver('sorts', SortResolverInterface::class);

            return $filter();
        });
    }
}
