<?php

namespace Mitoop\LaravelQueryBuilder\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeFilterCommand extends GeneratorCommand
{
    protected $name = 'make:filter';

    protected $description = 'Create a new filter class';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/filter.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Http\Filters';
    }
}
