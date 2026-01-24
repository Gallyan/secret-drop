<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EncryptionSanityTest extends DuskTestCase
{
    /**
     * Test full cycle: create text secret → read → verify decryption.
     */
    public function test_text_secret_encrypt_decrypt_cycle(): void
    {
        $secretText = 'Hello, this is my secret message! '.uniqid();

        $this->browse(function (Browser $browser) use ($secretText) {
            // Create secret
            $browser->visit('/')
                ->waitFor('#secret', 10)
                ->type('#secret', $secretText)
                ->select('#expiration', '1h')
                ->press('@submit-button')
                ->waitFor('#shareUrl', 15);

            // Get the share URL
            $shareUrl = $browser->value('#shareUrl');
            $this->assertNotEmpty($shareUrl, 'Share URL should be generated');
            $this->assertStringContainsString('#', $shareUrl, 'URL should contain key fragment');

            // Visit the share URL and verify decryption
            $browser->visit($shareUrl)
                ->waitFor('pre', 15)
                ->assertSeeIn('pre', $secretText);
        });
    }

    /**
     * Test secret with passphrase protection.
     */
    public function test_passphrase_protected_secret(): void
    {
        $secretText = 'Protected secret '.uniqid();
        $passphrase = 'MySecretPass123';

        $this->browse(function (Browser $browser) use ($secretText, $passphrase) {
            // Create secret with passphrase
            $browser->visit('/')
                ->waitFor('#secret', 10)
                ->type('#secret', $secretText)
                ->click('@advanced-options-toggle')
                ->waitFor('#passphrase')
                ->type('#passphrase', $passphrase)
                ->press('@submit-button')
                ->waitFor('#shareUrl', 15);

            $shareUrl = $browser->value('#shareUrl');

            // Visit URL - should ask for passphrase
            $browser->visit($shareUrl)
                ->waitFor('#passphrase', 10)
                ->assertVisible('#passphrase');

            // Enter wrong passphrase - should fail
            $browser->type('#passphrase', 'WrongPassword')
                ->press('@decrypt-button')
                ->waitFor('[role="alert"]', 5)
                ->assertPresent('[role="alert"]');

            // Retry with correct passphrase
            $browser->press('@retry-button')
                ->pause(500)
                ->clear('#passphrase')
                ->type('#passphrase', $passphrase)
                ->press('@decrypt-button')
                ->waitFor('pre', 15)
                ->assertSeeIn('pre', $secretText);
        });
    }

    /**
     * Test single-use secret destruction after read.
     */
    public function test_single_use_secret_destroyed_after_read(): void
    {
        $secretText = 'One-time secret '.uniqid();

        $this->browse(function (Browser $browser) use ($secretText) {
            // Create single-use secret
            $browser->visit('/')
                ->waitFor('#secret', 10)
                ->type('#secret', $secretText)
                ->check('#usageUnique')
                ->press('@submit-button')
                ->waitFor('#shareUrl', 15);

            $shareUrl = $browser->value('#shareUrl');

            // First read - should work
            $browser->visit($shareUrl)
                ->waitFor('pre', 15)
                ->assertSeeIn('pre', $secretText);

            // Second read - should fail (secret destroyed)
            $browser->visit($shareUrl)
                ->pause(3000)
                ->assertDontSee($secretText)
                ->assertPresent('[role="alert"]');
        });
    }

    /**
     * Test split mode: URL and key transmitted separately.
     */
    public function test_split_mode_separate_key(): void
    {
        $secretText = 'Split mode secret '.uniqid();

        $this->browse(function (Browser $browser) use ($secretText) {
            // Create secret with split mode
            $browser->visit('/')
                ->waitFor('#secret', 10)
                ->type('#secret', $secretText)
                ->click('@advanced-options-toggle')
                ->waitFor('#splitMode')
                ->check('#splitMode')
                ->press('@submit-button')
                ->waitFor('#shareUrlSplit', 15);

            // Get URL (without key) and key separately
            $shareUrl = $browser->value('#shareUrlSplit');
            $shareKey = $browser->value('#shareKeySplit');

            $this->assertNotEmpty($shareUrl, 'Share URL should be generated');
            $this->assertNotEmpty($shareKey, 'Share key should be generated');
            $this->assertStringNotContainsString('#', $shareUrl, 'URL should not contain fragment in split mode');

            // Visit URL - should ask for manual key input
            $browser->visit($shareUrl)
                ->waitFor('#manualKey', 10)
                ->assertVisible('#manualKey');

            // Enter the key and decrypt
            $browser->type('#manualKey', $shareKey)
                ->press('@unlock-button')
                ->waitFor('pre', 15)
                ->assertSeeIn('pre', $secretText);
        });
    }
}
