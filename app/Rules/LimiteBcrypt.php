<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LimiteBcrypt implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // PHP 8.4 rechaza bcrypt por encima de 72 bytes, incluidos caracteres multibyte.
        if (config('hashing.driver', 'bcrypt') === 'bcrypt' && is_string($value) && strlen($value) > 72) {
            $fail('La contraseña admite como máximo 72 bytes con el algoritmo configurado.');
        }
    }
}
