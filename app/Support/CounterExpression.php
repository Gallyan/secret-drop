<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Builds the "add to the current value" expression used by the upsert counters.
 *
 * Funnelling every such expression through an int-only signature is what makes
 * it safe: no caller can inject anything through an integer, which is why the
 * literal-string warning is suppressed for this file alone.
 */
class CounterExpression
{
    public static function addTo(int $amount): Expression
    {
        return DB::raw('count + '.$amount);
    }
}
