<?php

namespace Database\Seeders;

use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\StatsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Génération des secrets de démo...');
        $this->seedSecrets();

        $this->command->info('Génération des statistiques...');
        $this->seedStats();

        $this->command->info('Génération des magic links...');
        $this->seedMagicLinks();

        $this->command->info('Données de démo générées avec succès !');
    }

    private function seedSecrets(): void
    {
        $email = 'demo@example.com';

        // Secrets actifs
        Secret::factory()->count(5)->withCreatorEmail($email)->create();
        Secret::factory()->count(3)->file()->withCreatorEmail($email)->create();
        Secret::factory()->count(2)->singleUse()->withCreatorEmail($email)->create();
        Secret::factory()->count(2)->withMaxViews(5)->withCreatorEmail($email)->create();
        Secret::factory()->count(2)->withPassphrase()->withCreatorEmail($email)->create();

        // Secrets lus
        Secret::factory()->count(3)->read()->withCreatorEmail($email)->create();
        Secret::factory()->count(2)->file()->read(2)->withCreatorEmail($email)->create();

        // Secrets expirés
        Secret::factory()->count(3)->expired()->withCreatorEmail($email)->create();

        // Secrets révoqués
        Secret::factory()->count(2)->revoked()->withCreatorEmail($email)->create();

        // Secrets usage unique consommés
        Secret::factory()->count(2)->singleUse()->read()->withCreatorEmail($email)->create();

        // Secrets ayant atteint max_views
        Secret::factory()->count(2)->withMaxViews(3)->read(3)->withCreatorEmail($email)->create();

        $this->command->info('  -> '.Secret::count().' secrets créés');
    }

    private function seedStats(): void
    {
        // Nettoyer les stats existantes
        DB::table('stats_daily')->delete();

        $metrics = [
            StatsService::SECRETS_CREATED_TEXT => [5, 15],
            StatsService::SECRETS_CREATED_FILE => [1, 5],
            StatsService::SECRETS_WITH_PASSPHRASE => [0, 3],
            StatsService::SECRETS_SINGLE_USE => [2, 8],
            StatsService::SECRETS_WITH_MAX_VIEWS => [0, 4],
            StatsService::TOTAL_FILE_SIZE_BYTES => [100000, 5000000],
            StatsService::SECRETS_READ => [3, 12],
            StatsService::SECRETS_EXPIRED_UNREAD => [0, 3],
            StatsService::SECRETS_REVOKED => [0, 2],
            StatsService::SECRETS_MAX_VIEWS_REACHED => [0, 2],
            StatsService::MAGIC_LINKS_REQUESTED => [1, 5],
            StatsService::MAGIC_LINKS_USED => [1, 4],
            StatsService::SECRETS_EXTENDED => [0, 2],
        ];

        $days = 90;

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            foreach ($metrics as $metric => $range) {
                $count = fake()->numberBetween($range[0], $range[1]);

                if ($count > 0) {
                    DB::table('stats_daily')->insert([
                        'date' => $date,
                        'metric' => $metric,
                        'count' => $count,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('  -> 90 jours de statistiques générées');
    }

    private function seedMagicLinks(): void
    {
        $email = 'demo@example.com';

        // Magic links utilisés
        MagicLink::factory()->count(5)->forEmail($email)->used()->create();

        // Magic links expirés
        MagicLink::factory()->count(3)->forEmail($email)->expired()->create();

        // Magic link valide
        MagicLink::factory()->forEmail($email)->valid()->withToken('demo-token')->create();

        $this->command->info('  -> '.MagicLink::count().' magic links créés');
    }
}
