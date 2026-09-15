<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    /** Vérifie que /contact redirige en 302 vers le mailto de l'email de contact configuré. */
    public function testContactRedirectsToConfiguredEmail(): void
    {
        config(['legal.contact_email' => 'test@example.com', 'mail.from.address' => 'fallback@example.com']);

        $response = $this->get('/contact');

        $response->assertFound();
        $response->assertRedirect('mailto:test@example.com');
    }

    /** Vérifie que /contact retombe en 302 sur l'adresse mail.from quand aucun email de contact n'est configuré. */
    public function testContactFallsBackToMailFromAddress(): void
    {
        config(['legal.contact_email' => null, 'mail.from.address' => 'fallback@example.com']);

        $response = $this->get('/contact');

        $response->assertFound();
        $response->assertRedirect('mailto:fallback@example.com');
    }
}
