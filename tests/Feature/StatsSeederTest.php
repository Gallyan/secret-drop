<?php

namespace Tests\Feature;

use Database\Seeders\StatsSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatsSeederTest extends TestCase
{
    /** Vérifie que le seeder cumule les pages vues humaines par heure locale quand plusieurs pages tombent sur la même heure. */
    public function testAccumulatesHumanPageviewsIntoLocalHoursOnCollision(): void
    {
        $this->travelTo('2026-09-15 10:00:00');
        mt_srand(20260915);

        $this->seed(StatsSeeder::class);

        mt_srand();
        $humanPageviews = DB::table('stats_pageviews')->where('is_bot', false);
        $this->assertLessThan($humanPageviews->count(), DB::table('stats_local_hours')->count());
        $this->assertSame(
            (int) $humanPageviews->sum('count'),
            (int) DB::table('stats_local_hours')->sum('count'),
        );
    }
}
