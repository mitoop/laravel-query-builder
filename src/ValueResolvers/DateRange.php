<?php

namespace Mitoop\LaravelQueryBuilder\ValueResolvers;

use Illuminate\Support\Carbon;
use Mitoop\LaravelQueryBuilder\Contracts\ValueResolver;
use Throwable;

class DateRange implements ValueResolver
{
    public function resolve($value): ?array
    {
        if (! is_array($value) || count($value) !== 2) {
            return null;
        }

        try {
            [$start, $end] = $value;
            $start = Carbon::parse($start)->startOfDay();
            $end = Carbon::parse($end)->endOfDay();
        } catch (Throwable) {
            return null;
        }

        return [$start, $end];
    }
}
