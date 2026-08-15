<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;

/**
 * Abstrae "convertir un texto en un vector de embedding" del proveedor concreto
 * (OpenAI hoy, intercambiable después).
 */
interface EmbeddingProvider
{
    /**
     * @return float[] vector de embedding.
     * @throws RequestException si el proveedor devuelve un error.
     */
    public function embed(string $text): array;
}
