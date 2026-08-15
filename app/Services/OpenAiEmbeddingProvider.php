<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAiEmbeddingProvider implements EmbeddingProvider
{
    private const ENDPOINT = 'https://api.openai.com/v1/embeddings';
    private const MODEL = 'text-embedding-3-small';
    private const TIMEOUT = 15;

    public function embed(string $text): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(self::TIMEOUT)
            ->post(self::ENDPOINT, [
                'model' => self::MODEL,
                'input' => $text,
            ]);

        $response->throw();

        return $response->json('data.0.embedding') ?? [];
    }
}
