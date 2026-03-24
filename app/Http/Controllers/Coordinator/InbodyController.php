<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\InbodyRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class InbodyController extends Controller
{
    public function create(User $patient)
    {
        $records = $patient->inbodyRecords()->orderByDesc('test_date')->get();
        return view('coordinator.inbody.create', compact('patient', 'records'));
    }

    /**
     * Receive one or more InBody images, send all to Groq vision, return extracted JSON.
     */
    public function extract(Request $request, User $patient): JsonResponse
    {
        $request->validate(['images' => 'required|array|min:1|max:5', 'images.*' => 'image|max:10240']);

        $prompt = <<<'PROMPT'
Te mando una o varias hojas del mismo reporte InBody. Analizalas en conjunto y extraé todos los datos disponibles.
Respondé ÚNICAMENTE con un objeto JSON válido, sin texto adicional, con exactamente estas claves
(usá null si el valor no aparece en ninguna de las imágenes):

{
  "test_date":            "YYYY-MM-DD",
  "weight":               0.0,
  "skeletal_muscle_mass": 0.0,
  "body_fat_mass":        0.0,
  "body_fat_percentage":  0.0,
  "bmi":                  0.0,
  "basal_metabolic_rate": 0,
  "visceral_fat_level":   0.0,
  "total_body_water":     0.0,
  "proteins":             0.0,
  "minerals":             0.0,
  "inbody_score":         0,
  "obesity_degree":       0.0
}

Importante:
- "test_date" debe ser la fecha del estudio (no hoy), formato YYYY-MM-DD
- Todos los pesos y masas en kg
- "basal_metabolic_rate" en kcal (número entero)
- "inbody_score" es el puntaje InBody (0-100, número entero)
- "obesity_degree" es el porcentaje de obesidad (puede ser negativo si está por debajo del rango)
PROMPT;

        // Build content blocks: text prompt + one image block per file
        $content = [['type' => 'text', 'text' => $prompt]];
        $tmpPaths = [];

        foreach ($request->file('images') as $file) {
            $path       = $file->store('inbody-tmp', 'local');
            $tmpPaths[] = $path;
            $base64     = base64_encode(Storage::disk('local')->get($path));
            $mimeType   = $file->getMimeType();
            $content[]  = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64}"]];
        }

        $response = Http::withToken(config('services.groq.key'))
            ->timeout(90)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'max_tokens' => 600,
                'messages'   => [['role' => 'user', 'content' => $content]],
            ]);

        // Clean up temp files
        foreach ($tmpPaths as $p) Storage::disk('local')->delete($p);

        if ($response->failed()) {
            $groqError = $response->json('error.message') ?? $response->body();
            return response()->json(['error' => "Error de Groq ({$response->status()}): {$groqError}"], 502);
        }

        $raw = $response->json('choices.0.message.content') ?? '';

        // Extract JSON block if model wraps it in markdown
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $raw, $m)) {
            $raw = $m[1];
        }

        $data = json_decode(trim($raw), true);

        if (!is_array($data)) {
            return response()->json([
                'error' => 'No se pudo extraer los datos. Intentá con una imagen más clara.',
                'raw'   => substr($raw, 0, 300),
            ], 422);
        }

        return response()->json($data);
    }

    public function store(Request $request, User $patient)
    {
        $validated = $request->validate([
            'test_date'            => 'required|date',
            'weight'               => 'nullable|numeric|min:20|max:300',
            'skeletal_muscle_mass' => 'nullable|numeric|min:0',
            'body_fat_mass'        => 'nullable|numeric|min:0',
            'body_fat_percentage'  => 'nullable|numeric|min:0|max:100',
            'bmi'                  => 'nullable|numeric|min:0',
            'basal_metabolic_rate' => 'nullable|integer|min:0',
            'visceral_fat_level'   => 'nullable|numeric|min:0',
            'total_body_water'     => 'nullable|numeric|min:0',
            'proteins'             => 'nullable|numeric|min:0',
            'minerals'             => 'nullable|numeric|min:0',
            'inbody_score'         => 'nullable|integer|min:0|max:100',
            'obesity_degree'       => 'nullable|numeric',
            'notes'                => 'nullable|string|max:1000',
            'images'               => 'nullable|array|max:5',
            'images.*'             => 'image|max:10240',
        ]);

        // Store first uploaded image (main reference)
        $imagePath = null;
        if ($request->hasFile('images')) {
            $imagePath = $request->file('images')[0]->store("inbody/{$patient->id}", 'public');
        }

        $patient->inbodyRecords()->create(array_merge($validated, ['image_path' => $imagePath]));

        return redirect()->route('coordinator.patients.show', $patient)
            ->with('success', 'Registro InBody guardado correctamente.');
    }

    public function destroy(User $patient, InbodyRecord $record)
    {
        if ($record->image_path) {
            Storage::disk('public')->delete($record->image_path);
        }
        $record->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
