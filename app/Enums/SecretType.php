<?php

namespace App\Enums;

enum SecretType: string
{
    case Text = 'text';
    case File = 'file';
}
