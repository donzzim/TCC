<?php

namespace App\Helper;

class CpfHelper
{
    public static function format(?string $cpf): string
    {
        if (blank($cpf)) {
            return '';
        }

        $digits = str_pad(preg_replace('/\D/', '', $cpf), 11, '0', STR_PAD_LEFT);

        return substr($digits, 0, 3).'.'.substr($digits, 3, 3).'.'.substr($digits, 6, 3).'-'.substr($digits, 9, 2);
    }
}
