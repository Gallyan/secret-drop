<?php

namespace App\Enums;

enum SecretType: string
{
    case Text = 'text';
    case File = 'file';

    public function isText(): bool
    {
        return $this === self::Text;
    }

    public function isFile(): bool
    {
        return $this === self::File;
    }
}
