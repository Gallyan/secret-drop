<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationTypographyTest extends TestCase
{
    private const LOCALES = ['ar', 'de', 'en', 'es', 'fr', 'it', 'ja', 'ko', 'nl', 'pl', 'pt'];

    private const STRAIGHT_APOSTROPHE = "/\p{L}'[\p{L}:]/u";

    /** Vérifie qu'aucune traduction de la locale ne viole la règle typographique donnée. */
    #[DataProvider('typographyRules')]
    public function testMessagesFollowTypographyRule(string $locale, string $pattern): void
    {
        $offendingKeys = array_keys(array_filter(
            $this->messages($locale),
            fn (string $message): bool => preg_match($pattern, $message) === 1,
        ));

        $this->assertSame([], $offendingKeys);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function typographyRules(): array
    {
        $rules = [
            'fr : apostrophe droite au lieu de ’' => ['fr', self::STRAIGHT_APOSTROPHE],
            'fr : espace sécable avant : ; ! ?' => ['fr', '/ [:;!?](?=[\s<"\')|.,]|$)/u'],
            'fr : guillemets sans espace insécable' => ['fr', '/«(?!\x{00A0})|(?<!\x{00A0})»/u'],
            'fr : espace sécable entre nombre et unité' => ['fr', '/(\d|:[a-z_]+) (Mo|caractères|heures?|jours?|minutes|s|octets|lectures?|vues?|secrets?|%)(?!\p{L})/u'],
            'fr : espace sécable dans un millier' => ['fr', '/\d \d{3}(?!\d)/'],
            'it : apostrophe droite au lieu de ’' => ['it', self::STRAIGHT_APOSTROPHE],
        ];

        foreach (self::LOCALES as $locale) {
            $rules["{$locale} : trois points au lieu de …"] = [$locale, '/\.\.\./'];
            $rules["{$locale} : guillemets droits hors balise HTML"] = [$locale, '/"(?![^<]*>)/'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function messages(string $locale): array
    {
        return require lang_path("{$locale}/messages.php");
    }
}
