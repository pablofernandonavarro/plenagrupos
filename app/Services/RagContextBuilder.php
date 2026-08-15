<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class RagContextBuilder
{
    private const SOURCE_LABELS = [
        'weight_note' => 'Nota de registro de peso',
        'inbody_note' => 'Nota de estudio InBody',
        'coordinator_note' => 'Observación del coordinador (sesión)',
        'appointment_note' => 'Nota de turno',
        'personal_goal' => 'Objetivo personal',
        'patient_status_note' => 'Nota de cambio de estado',
    ];

    public function __construct(
        private StructuredContextBuilder $structured,
        private VectorSearchService $vectorSearch,
    ) {
    }

    /**
     * Arma el contexto completo (estructurado + semántico) para responder una pregunta
     * sobre un paciente puntual. Siempre acotado a $patient->id. Devuelve también los
     * fragmentos semánticos usados, para poder auditarlos (ai_messages.retrieved_context)
     * sin tener que volver a embeder la pregunta.
     */
    public function build(User $patient, string $question): array
    {
        $structuredBlock = $this->structured->build($patient);
        $semanticMatches = $this->vectorSearch->search($patient, $question);

        $semanticBlock = $this->formatSemanticMatches($semanticMatches);

        return [
            'context' => $structuredBlock."\n".$semanticBlock,
            'matches' => $semanticMatches,
        ];
    }

    private function formatSemanticMatches(Collection $matches): string
    {
        if ($matches->isEmpty()) {
            return "=== NOTAS RELACIONADAS CON LA PREGUNTA ===\n  Sin coincidencias relevantes.\n";
        }

        $lines = $matches->map(function (array $m) {
            $label = self::SOURCE_LABELS[$m['source_type']] ?? $m['source_type'];

            return "  · [{$label}] \"{$m['content']}\"";
        })->join("\n");

        return "=== NOTAS RELACIONADAS CON LA PREGUNTA ===\n{$lines}\n";
    }
}
