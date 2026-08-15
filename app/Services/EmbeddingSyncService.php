<?php

namespace App\Services;

use App\Models\PatientEmbedding;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mantiene sincronizado el embedding de un fragmento de texto libre (nota de peso, InBody,
 * observación del coordinador, nota de turno, objetivo personal, etc.) con su fuente.
 * Best-effort: si el proveedor de embeddings falla, no rompe el flujo que guardó la nota
 * (mismo criterio que AppointmentWhatsapp con WhatsApp).
 */
class EmbeddingSyncService
{
    public function __construct(private EmbeddingProvider $provider)
    {
    }

    public function sync(string $sourceType, int $patientId, int $sourceId, ?string $text): void
    {
        $text = trim((string) $text);

        if ($text === '') {
            PatientEmbedding::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->delete();

            return;
        }

        try {
            $vector = $this->provider->embed($text);
        } catch (Throwable $e) {
            Log::warning("EmbeddingSyncService: fallo al generar embedding de {$sourceType}#{$sourceId}", [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($vector)) {
            return;
        }

        PatientEmbedding::updateOrCreate(
            ['source_type' => $sourceType, 'source_id' => $sourceId],
            ['patient_id' => $patientId, 'content' => $text, 'embedding' => $vector]
        );
    }
}
