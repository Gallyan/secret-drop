<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Crypto\DkimSigner;

/** Intercepts outgoing emails to attach a DKIM signature using the configured private key. */
class SignEmailWithDkim
{
    public function handle(MessageSending $event): void
    {
        $domain = config_string('mail.dkim.domain');
        $selector = config_string('mail.dkim.selector');
        $privateKeyPath = config_string('mail.dkim.private_key_path');

        if ($domain === '' || $selector === '' || $privateKeyPath === '') {
            return;
        }

        $fullPath = base_path($privateKeyPath);

        if (! file_exists($fullPath)) {
            Log::warning('DKIM private key not found', ['path' => $privateKeyPath]);

            return;
        }

        $privateKey = file_get_contents($fullPath);

        if ($privateKey === false) {
            Log::warning('DKIM private key is not readable', ['path' => $privateKeyPath]);

            return;
        }

        $passphrase = config_string('mail.dkim.passphrase');

        $signer = new DkimSigner($privateKey, $domain, $selector, [], $passphrase);
        $signedMessage = $signer->sign($event->message);

        $dkimHeader = $signedMessage->getHeaders()->get('DKIM-Signature');

        if ($dkimHeader !== null) {
            $event->message->getHeaders()->add($dkimHeader);
        }
    }
}
