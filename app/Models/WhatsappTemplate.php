<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = ['key', 'body', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Renderiza la plantilla $key reemplazando {placeholder} por los valores de $params.
     * Devuelve null si la plantilla no existe o está desactivada (no se debe enviar nada).
     */
    public static function render(string $key, array $params): ?string
    {
        $template = static::where('key', $key)->where('active', true)->first();

        if (! $template) {
            return null;
        }

        $replacements = [];
        foreach ($params as $name => $value) {
            $replacements["{{$name}}"] = $value;
        }

        return strtr($template->body, $replacements);
    }
}
