<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class GeoController extends Controller
{
    public function llmsTxt(): Response
    {
        $fullUrl = url('/llms-full.txt');
        $legalUrl = url('/legal');

        $content = <<<TXT
            # Secret Drop

            > Zero-knowledge secret sharing application

            For full documentation, see: {$fullUrl}

            Secret Drop allows users to securely share secrets (text or files) with end-to-end encryption. The server never sees plaintext data.

            ## How it works

            1. User creates a secret (text or file) in their browser
            2. Browser generates a random encryption key
            3. Content is encrypted client-side using AES-256-GCM
            4. Only the encrypted data is sent to the server
            5. User receives a link with the decryption key in the URL fragment (never sent to server)
            6. Recipient opens the link, browser decrypts the content locally

            ## Key Features

            - Client-side encryption (WebCrypto API)
            - Zero-knowledge architecture
            - Optional passphrase protection
            - Auto-expiration (1 hour to 90 days)
            - Single-use or limited views options
            - File support up to 10MB
            - No account required
            - Available in 11 languages

            ## Privacy

            - Encryption keys never touch the server
            - No third-party tracking, no cookies
            - Minimal data retention
            - GDPR compliant

            ## Contact

            See the legal notice page at {$legalUrl} for contact information.
            TXT;

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function llmsFullTxt(): Response
    {
        $legalUrl = url('/legal');
        $content = file_get_contents(resource_path('llms-full.txt'));
        $content = str_replace('LEGAL_URL', $legalUrl, $content);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function securityTxt(): Response
    {
        $email = config('legal.contact_email', config('mail.from.address'));
        $canonical = url('/.well-known/security.txt');
        $expires = now()->addYear()->utc()->format('Y-m-d\TH:i:s\Z');

        $content = <<<TXT
            Contact: mailto:{$email}
            Expires: {$expires}
            Preferred-Languages: fr, en
            Canonical: {$canonical}
            TXT;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
