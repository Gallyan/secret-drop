<?php

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    /**
     * Session of a super admin authenticated through the magic link, valid for 15 minutes.
     *
     * @return array{super_admin_verified: true, super_admin_expires_at: int}
     */
    protected function superAdminSession(): array
    {
        return [
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->addMinutes(15)->timestamp,
        ];
    }
}
