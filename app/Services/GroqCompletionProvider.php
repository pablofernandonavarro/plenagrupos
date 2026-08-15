<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GroqCompletionProvider implements AiCompletionProvider
{
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    private const DEFAULT_MODEL = 'llama-3.3-70b-versatile';
    private const DEFAULT_MAX_TOKENS = 600;
    private const DEFAULT_TIMEOUT = 30;

    public function complete(array $messages, array $options = []): string
    {
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;
        unset($options['timeout']);

        $payload = array_merge([
            'model' => self::DEFAULT_MODEL,
            'max_tokens' => self::DEFAULT_MAX_TOKENS,
        ], $options, ['messages' => $messages]);

        $response = Http::withToken(config('services.groq.key'))
            ->timeout($timeout)
            ->post(self::ENDPOINT, $payload);

        $response->throw();

        return $response->json('choices.0.message.content') ?? '';
    }
}
