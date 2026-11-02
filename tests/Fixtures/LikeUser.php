<?php

namespace Mitoop\LaravelQueryBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LikeUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public function profile(): HasOne
    {
        return $this->hasOne(LikeProfile::class, 'user_id');
    }
}

class LikeProfile extends Model
{
    protected $guarded = [];
}
