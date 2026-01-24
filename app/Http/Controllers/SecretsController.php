<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SecretsController extends Controller
{
    public function create(): View
    {
        return view('secrets.create');
    }
}
