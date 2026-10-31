<?php

namespace Mitoop\LaravelQueryBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttributeScopedUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('active', 1);
    }
}
