<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\User;
use App\Services\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AiChatController extends Controller
{
    public function __construct()
    {
        if (! config('services.ai_chat.enabled')) {
            throw new NotFoundHttpException;
        }
    }

    public function index(User $patient): JsonResponse
    {
        $conversation = $this->conversationFor($patient)->with('messages')->first();

        return response()->json([
            'messages' => $this->formatMessages($conversation),
        ]);
    }

    public function store(Request $request, User $patient, RagService $rag): JsonResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $conversation = $this->conversationFor($patient)->first();
        $conversation = $rag->ask($patient, $request->user(), $data['question'], $conversation);

        return response()->json([
            'messages' => $this->formatMessages($conversation),
        ]);
    }

    private function conversationFor(User $patient)
    {
        return AiConversation::where('patient_id', $patient->id)
            ->where('coordinator_id', auth()->id())
            ->latest('created_at');
    }

    private function formatMessages(?AiConversation $conversation): array
    {
        return $conversation?->messages
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all() ?? [];
    }
}
