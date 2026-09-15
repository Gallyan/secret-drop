<?php

namespace Tests\Feature;

use App\Listeners\SignEmailWithDkim;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class SignEmailWithDkimTest extends TestCase
{
    private const KEY_PATH = 'tests/Fixtures/dkim/private.key';

    private const ENCRYPTED_KEY_PATH = 'tests/Fixtures/dkim/private-with-passphrase.key';

    /** Vérifie qu'aucune signature n'est ajoutée sans configuration DKIM. */
    public function testDoesNotSignWhenDkimIsNotConfigured(): void
    {
        $this->configureDkim(domain: '', selector: '', privateKeyPath: '');
        $event = new MessageSending($this->makeMessage());

        (new SignEmailWithDkim())->handle($event);

        $this->assertFalse($event->message->getHeaders()->has('DKIM-Signature'));
    }

    /** Vérifie qu'une clé introuvable laisse le message non signé et journalise un warning sans le chemin absolu. */
    public function testDoesNotSignAndWarnsWhenKeyFileIsMissing(): void
    {
        $this->configureDkim(privateKeyPath: 'nonexistent/path/private.key');
        Log::shouldReceive('warning')
            ->once()
            ->with('DKIM private key not found', ['path' => 'nonexistent/path/private.key']);
        $event = new MessageSending($this->makeMessage());

        (new SignEmailWithDkim())->handle($event);

        $this->assertFalse($event->message->getHeaders()->has('DKIM-Signature'));
    }

    /** Vérifie qu'une clé présente mais illisible laisse le message non signé et journalise un warning. */
    public function testDoesNotSignAndWarnsWhenKeyFileIsNotReadable(): void
    {
        $directory = sys_get_temp_dir().'/secret-drop-dkim-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($directory);
        $unreadableKeyPath = "{$directory}/private.key";
        File::copy(base_path(self::KEY_PATH), $unreadableKeyPath);
        chmod($unreadableKeyPath, 0000);

        try {
            if (is_readable($unreadableKeyPath)) {
                $this->markTestSkipped('Le processus lit les fichiers sans permission (root).');
            }

            $this->configureDkim(privateKeyPath: $unreadableKeyPath);
            Log::shouldReceive('warning')
                ->once()
                ->with('DKIM private key is not readable', ['path' => $unreadableKeyPath]);
            $event = new MessageSending($this->makeMessage());

            (new SignEmailWithDkim())->handle($event);

            $this->assertFalse($event->message->getHeaders()->has('DKIM-Signature'));
        } finally {
            chmod($unreadableKeyPath, 0600);
            File::deleteDirectory($directory);
        }
    }

    /** Vérifie la signature avec un chemin de clé relatif à la racine du projet. */
    public function testSignsMessageWithKeyPathRelativeToProjectRoot(): void
    {
        $this->configureDkim(privateKeyPath: self::KEY_PATH);
        $event = new MessageSending($this->makeMessage());

        (new SignEmailWithDkim())->handle($event);

        $this->assertSignedFor($event, 'example.com', 'secretdrop');
    }

    /** Vérifie la signature avec un chemin de clé absolu, utilisé tel quel. */
    public function testSignsMessageWithAbsoluteKeyPath(): void
    {
        $this->configureDkim(privateKeyPath: base_path(self::KEY_PATH));
        $event = new MessageSending($this->makeMessage());

        (new SignEmailWithDkim())->handle($event);

        $this->assertSignedFor($event, 'example.com', 'secretdrop');
    }

    /** Vérifie la signature avec une clé chiffrée par une passphrase configurée. */
    public function testSignsMessageWithPassphraseProtectedKey(): void
    {
        $this->configureDkim(privateKeyPath: self::ENCRYPTED_KEY_PATH, passphrase: 'test-passphrase');
        $event = new MessageSending($this->makeMessage());

        (new SignEmailWithDkim())->handle($event);

        $this->assertSignedFor($event, 'example.com', 'secretdrop');
    }

    private function configureDkim(
        string $domain = 'example.com',
        string $selector = 'secretdrop',
        string $privateKeyPath = self::KEY_PATH,
        string $passphrase = '',
    ): void {
        Config::set('mail.dkim.domain', $domain);
        Config::set('mail.dkim.selector', $selector);
        Config::set('mail.dkim.private_key_path', $privateKeyPath);
        Config::set('mail.dkim.passphrase', $passphrase);
    }

    private function makeMessage(): Email
    {
        return (new Email())
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test')
            ->text('Test body');
    }

    private function assertSignedFor(MessageSending $event, string $domain, string $selector): void
    {
        $signature = $event->message->getHeaders()->get('DKIM-Signature')?->getBodyAsString() ?? '';

        $this->assertStringContainsString("d={$domain};", $signature);
        $this->assertStringContainsString("s={$selector};", $signature);
        $this->assertMatchesRegularExpression('/b=[A-Za-z0-9+\/=\s]{300,}$/', $signature);
    }
}
