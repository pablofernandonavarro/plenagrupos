<?php

namespace App\Services;

use App\Models\PatientEmbedding;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class VectorSearchService
{
    public function __construct(private EmbeddingProvider $provider)
    {
    }

    /**
     * Busca los fragmentos de texto más relevantes de UN paciente para una pregunta dada.
     * Siempre acotado a $patient->id — nunca debe mezclar fragmentos de otro paciente.
     */
    public function search(User $patient, string $question, int $topK = 8): Collection
    {
        $embeddings = PatientEmbedding::where('patient_id', $patient->id)->get();

        if ($embeddings->isEmpty()) {
            return collect();
        }

        try {
            $queryVector = $this->provider->embed($question);
        } catch (Throwable $e) {
            Log::warning('VectorSearchService: fallo al generar embedding de la pregunta', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }

        if (empty($queryVector)) {
            return collect();
        }

        return $embeddings
            ->map(fn (PatientEmbedding $e) => [
                'source_type' => $e->source_type,
                'source_id' => $e->source_id,
                'content' => $e->content,
                'score' => self::cosineSimilarity($queryVector, $e->embedding),
            ])
            ->sortByDesc('score')
            ->take($topK)
            ->values();
    }

    public static function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));

        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
