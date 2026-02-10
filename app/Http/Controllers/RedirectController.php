<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect()->route('home', ['locale' => app()->getLocale()]);
    }

    public function howItWorks(): RedirectResponse
    {
        return redirect(localized_route('how-it-works'), 301);
    }

    public function useCases(): RedirectResponse
    {
        return redirect(localized_route('use-cases'), 301);
    }

    public function legal(): RedirectResponse
    {
        return redirect(localized_route('legal'), 301);
    }
}
