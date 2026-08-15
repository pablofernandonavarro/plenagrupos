<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Http\Client\RequestException;

class RagService
{
    private const MAX_HISTORY_MESSAGES = 12;

    public function __construct(
        private RagContextBuilder $contextBuilder,
        private AiCompletionProvider $ai,
    ) {
    }

    /**
     * Responde una pregunta del coordinador sobre un paciente puntual, dentro de una
     * conversación (nueva o existente). El contexto (SQL + semántico) se recalcula en
     * cada pregunta, siempre acotado a $patient->id.
     */
    public function ask(User $patient, User $coordinator, string $question, ?AiConversation $conversation = null): AiConversation
    {
        $conversation ??= AiConversation::create([
            'patient_id' => $patient->id,
            'coordinator_id' => $coordinator->id,
        ]);

        $built = $this->contextBuilder->build($patient, $question);

        $history = $conversation->messages()
            ->orderByDesc('created_at')
            ->take(self::MAX_HISTORY_MESSAGES)
            ->get()
            ->reverse()
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($patient, $built['context'])]],
            $history,
            [['role' => 'user', 'content' => $question]]
        );

        try {
            $answer = $this->ai->complete($messages);
        } catch (RequestException $e) {
            $answer = 'No se pudo generar una respuesta. Intentá nuevamente en unos segundos.';
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
            'retrieved_context' => [
                'semantic_matches' => $built['matches']->all(),
            ],
        ]);

        return $conversation->fresh('messages');
    }

    private function systemPrompt(User $patient, string $context): string
    {
        return 'Sos un asistente para coordinadores de grupos de control de peso/bienestar. '.
            "Respondés preguntas del coordinador sobre UN paciente puntual ({$patient->name}), ".
            "basándote EXCLUSIVAMENTE en los datos provistos abajo.\n".
            "Reglas estrictas:\n".
            "- Nunca inventes datos que no estén en el contexto. Si no tenés información suficiente para responder, decilo explícitamente.\n".
            "- No hagas diagnósticos clínicos ni sugieras tratamientos médicos — solo describí y contextualizá los datos disponibles.\n".
            "- Cuando cites un dato puntual (peso, nota, turno), mencioná la fecha de origen si está disponible.\n".
            "- No menciones scores, puntajes de relevancia ni metadatos internos — hablale al coordinador en lenguaje natural.\n".
            "- Respondé en español, de forma breve y concreta (2-5 oraciones salvo que se pida más detalle).\n\n".
            "=== DATOS DEL PACIENTE ===\n{$context}";
    }
}
