<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as BasePreventRequestForgery;

/**
 * Le front envoie le token CSRF via la meta tag (header X-CSRF-TOKEN),
 * le cookie XSRF-TOKEN lisible par JS est donc inutile et supprimé.
 */
class PreventRequestForgery extends BasePreventRequestForgery
{
    /** @var bool */
    protected $addHttpCookie = false;
}
