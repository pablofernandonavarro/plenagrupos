<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que el teléfono venga en formato apto para WhatsApp: código de país + número,
 * solo dígitos. Para Argentina exige el formato completo "54 9 + código de área (2 a 4
 * dígitos) + número", que siempre totaliza 13 dígitos — ni el "15" de discado local
 * (sobran dígitos) ni un código de área incompleto (faltan dígitos) cumplen esto, y son
 * los dos errores más comunes que hacen que WAHA rechace el envío.
 */
class WhatsappPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/[^\d]/', '', (string) $value);

        if ($digits === '' || ! preg_match('/^\d{10,15}$/', $digits)) {
            $fail('El teléfono debe tener solo números, con código de país, sin espacios ni el "15". Formato: 54 9 + código de área + número. Ejemplo: 5491122334455.');

            return;
        }

        if (str_starts_with($digits, '54') && (! str_starts_with($digits, '549') || strlen($digits) !== 13)) {
            $fail('Formato incompleto para Argentina. Tiene que ser 54 9 + código de área + número, 13 dígitos en total, sin el "15". Ejemplo: 5491122334455 (no 5401122334455, 541122334455, ni con el código de área incompleto).');
        }
    }
}
