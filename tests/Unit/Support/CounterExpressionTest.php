<?php

namespace Tests\Unit\Support;

use App\Support\CounterExpression;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CounterExpressionTest extends TestCase
{
    /** Vérifie que l'expression ajoute le montant demandé au compteur d'une ligne existante lors d'un upsert. */
    public function testAddsAmountToExistingCountOnUpsert(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        $row = ['date' => '2026-09-15', 'metric' => 'test_metric', 'created_at' => now(), 'updated_at' => now()];
        DB::table('stats_daily')->insert($row + ['count' => 3]);

        DB::table('stats_daily')->upsert(
            $row + ['count' => 4],
            ['date', 'metric'],
            ['count' => CounterExpression::addTo('stats_daily', 4)]
        );

        $this->assertDatabaseCount('stats_daily', 1);
        $this->assertDatabaseHas('stats_daily', ['date' => '2026-09-15', 'metric' => 'test_metric', 'count' => 7]);
    }

    /** Vérifie que l'upsert compilé pour PostgreSQL qualifie la colonne count par sa table, sans quoi elle est ambiguë avec excluded. */
    public function testQualifiesCountColumnWithTableForPostgresUpsert(): void
    {
        $connection = DB::connection();
        $grammar = new PostgresGrammar($connection);
        $query = (new Builder($connection, $grammar, $connection->getPostProcessor()))->from('stats_daily');

        $sql = $grammar->compileUpsert(
            $query,
            [['date' => '2026-09-15', 'metric' => 'test_metric', 'count' => 4]],
            ['date', 'metric'],
            ['count' => CounterExpression::addTo('stats_daily', 4)]
        );

        $this->assertStringEndsWith('do update set "count" = "stats_daily"."count" + 4', $sql);
    }
}
