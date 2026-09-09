<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function email(): RedirectResponse
    {
        $email = config_string('legal.contact_email', config_string('mail.from.address'));

        return redirect()->away("mailto:{$email}");
    }
}
