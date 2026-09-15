<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Once;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    private ?string $versionDirectory = null;

    protected function tearDown(): void
    {
        if ($this->versionDirectory !== null) {
            File::deleteDirectory($this->versionDirectory);
        }

        parent::tearDown();
    }

    /** @return array<string, array{string, int, string}> */
    public static function localizedNumbers(): array
    {
        return [
            'français : espace fine insécable et virgule' => ['fr', 2, "1\u{202F}234\u{202F}567,89"],
            'anglais : virgule et point' => ['en', 2, '1,234,567.89'],
            'allemand : point et virgule' => ['de', 2, '1.234.567,89'],
            'sans décimale, arrondi' => ['en', 0, '1,234,568'],
        ];
    }

    /** Vérifie que nfmt formate selon la locale active de l'application. */
    #[DataProvider('localizedNumbers')]
    public function testNfmtUsesActiveLocaleSeparators(string $locale, int $decimals, string $expected): void
    {
        app()->setLocale($locale);

        $this->assertSame($expected, nfmt(1234567.891, $decimals));
    }

    /** @return array<string, array{mixed, string}> */
    public static function configValues(): array
    {
        return [
            'chaîne' => ['https://secret.test', 'https://secret.test'],
            'chaîne vide' => ['', 'fallback'],
            'null' => [null, 'fallback'],
            'entier' => [42, 'fallback'],
            'tableau' => [['a'], 'fallback'],
        ];
    }

    /** Vérifie que config_string ne renvoie que des chaînes non vides, sinon la valeur par défaut. */
    #[DataProvider('configValues')]
    public function testConfigStringFallsBackForMissingOrNonStringValues(mixed $value, string $expected): void
    {
        config(['testing.value' => $value]);

        $this->assertSame($expected, config_string('testing.value', 'fallback'));
    }

    /** Vérifie que config_string renvoie la valeur par défaut pour une clé absente. */
    public function testConfigStringFallsBackForAbsentKey(): void
    {
        $this->assertSame('', config_string('testing.absent'));
    }

    /** Vérifie que app_version renvoie null sans fichier VERSION. */
    public function testAppVersionIsNullWithoutVersionFile(): void
    {
        $this->useVersionFile(null);

        $this->assertNull(app_version());
    }

    /** @return array<string, array{string, array{version: ?string, hash: string, date: ?string}|null}> */
    public static function versionFiles(): array
    {
        return [
            'hash vide' => ["\n2026-09-01\nv1.4.0\n", null],
            'hash seul' => ["abc1234\n", ['version' => null, 'hash' => 'abc1234', 'date' => null]],
            'hash et date' => ["abc1234\n2026-09-01", ['version' => null, 'hash' => 'abc1234', 'date' => '2026-09-01']],
            'hash, date et version' => [
                " abc1234 \n 2026-09-01 \n v1.4.0 \n",
                ['version' => 'v1.4.0', 'hash' => 'abc1234', 'date' => '2026-09-01'],
            ],
        ];
    }

    /**
     * Vérifie la lecture des lignes hash, date et version du fichier VERSION.
     *
     * @param  array{version: ?string, hash: string, date: ?string}|null  $expected
     */
    #[DataProvider('versionFiles')]
    public function testAppVersionReadsVersionFileLines(string $contents, ?array $expected): void
    {
        $this->useVersionFile($contents);

        $this->assertSame($expected, app_version());
    }

    /** Vérifie que csp_nonce renvoie la même valeur aléatoire de 32 caractères pendant toute la requête. */
    public function testCspNonceIsStableWithinARequest(): void
    {
        $nonce = csp_nonce();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $nonce);
        $this->assertSame($nonce, csp_nonce());
    }

    private function useVersionFile(?string $contents): void
    {
        $this->versionDirectory = sys_get_temp_dir().'/secret-drop-helpers-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->versionDirectory);

        if ($contents !== null) {
            File::put("{$this->versionDirectory}/VERSION", $contents);
        }

        $this->app->setBasePath($this->versionDirectory);
        Once::flush();
    }
}
