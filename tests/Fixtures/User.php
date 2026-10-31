<?php

namespace Mitoop\LaravelQueryBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    public function scopeActive(Builder $builder): void
    {
        $builder->where('active', 1);
    }

    public function scopeEmailVerified(Builder $builder): void
    {
        $builder->whereNotNull('email_verified_at');
    }
}
