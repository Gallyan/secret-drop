<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/** Redirects non-localized URLs to the locale negotiated from Accept-Language, so each response varies on that header. */
class RedirectController extends Controller
{
    public function root(): RedirectResponse
    {
        return $this->negotiated(redirect(route('home', ['locale' => app()->getLocale()]).'/'));
    }

    public function howItWorks(): RedirectResponse
    {
        return $this->negotiated(redirect(localized_route('how-it-works'), 301));
    }

    public function useCases(): RedirectResponse
    {
        return $this->negotiated(redirect(localized_route('use-cases'), 301));
    }

    public function legal(): RedirectResponse
    {
        return $this->negotiated(redirect(localized_route('legal'), 301));
    }

    public function faq(): RedirectResponse
    {
        return $this->negotiated(redirect(localized_route('faq'), 301));
    }

    public function admin(): RedirectResponse
    {
        return $this->negotiated(redirect()->route('admin.index', ['locale' => app()->getLocale()]));
    }

    public function superadmin(): RedirectResponse
    {
        return $this->negotiated(redirect()->route('superadmin.index', ['locale' => app()->getLocale()]));
    }

    private function negotiated(RedirectResponse $response): RedirectResponse
    {
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
