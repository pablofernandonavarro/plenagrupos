<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;

/**
 * Abstrae "pedirle una respuesta de texto a un modelo de IA" del proveedor concreto
 * (Groq hoy, intercambiable después) para no atar el resto de la app a un solo proveedor.
 */
interface AiCompletionProvider
{
    /**
     * @param array $messages mensajes estilo chat OpenAI-compatible (role + content; content
     *                        puede ser texto o, para vision, un array de bloques text/image_url).
     * @param array $options  overrides opcionales: model, max_tokens, timeout, etc.
     * @return string texto de la respuesta del modelo.
     * @throws RequestException si el proveedor devuelve un error.
     */
    public function complete(array $messages, array $options = []): string;
}
