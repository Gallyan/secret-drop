# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

```bash
# Initial setup (install deps, generate key, migrate, build assets)
composer setup

# Development server with concurrent processes (Laravel, queue, pail logs, Vite)
composer dev

# Run tests
composer test

# Run a single test
php artisan test --filter=TestClassName
php artisan test tests/Feature/ExampleTest.php

# Build frontend assets
npm run build
npm run dev
```

## Architecture

### Stack
- Laravel 12 with PHP 8.2+
- Alpine.js 3.14 + Tailwind CSS 4.0 + Vite
- SQLite database (database/database.sqlite)

### Security Layer
This project has a security-first architecture with custom middleware and logging:

**Middleware** (`app/Http/Middleware/`):
- `ForceHttps` - Redirects to HTTPS and forces scheme in production
- `SecurityHeaders` - Adds CSP, HSTS, X-Frame-Options, and other security headers

**CSP Nonce System**:
- Nonce generated as singleton in `AppServiceProvider`
- Helper function `csp_nonce()` in `app/helpers.php`
- Blade directive `@nonce` for inline scripts/styles
- Vite configured to use CSP nonce automatically

**Log Sanitization** (`app/Logging/SanitizeProcessor.php`):
- Custom Monolog processor that redacts sensitive data (passwords, tokens, API keys, SSN, etc.)
- Applied to all logging channels via `config/logging.php`

### Key Files
- `app/helpers.php` - Global helper functions (autoloaded via composer)
- `app/Providers/AppServiceProvider.php` - CSP nonce registration and Blade/Vite configuration
