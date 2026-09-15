<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * "Add to the current value" expression used by the upsert counters.
 *
 * The column is qualified with its table, wrapped by the grammar compiling the
 * query: PostgreSQL rejects a bare `count` in `on conflict do update` because it
 * is ambiguous with `excluded.count`. The amount is an integer, so nothing can
 * be injected through it.
 */
final class CounterExpression implements Expression
{
    private function __construct(
        private string $table,
        private int $amount,
    ) {
    }

    public static function addTo(string $table, int $amount): self
    {
        return new self($table, $amount);
    }

    public function getValue(Grammar $grammar): string
    {
        return "{$grammar->wrap("{$this->table}.count")} + {$this->amount}";
    }
}
