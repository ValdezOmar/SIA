<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CantidadEntera implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || (float) $value !== (float) (int) $value) {
            $fail('La cantidad debe ser un número entero de unidades.');
        }
    }
}
