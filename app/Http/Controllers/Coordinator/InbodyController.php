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
     * Receive an InBody image, send to Groq vision, return extracted JSON.
     */
    public function extract(Request $request, User $patient): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:10240']);

        $path     = $request->file('image')->store('inbody-tmp', 'local');
        $base64   = base64_encode(file_get_contents(storage_path('app/' . $path)));
        $mimeType = $request->file('image')->getMimeType();

        $prompt = <<<'PROMPT'
Analizá esta imagen de un reporte InBody y extraé los siguientes datos.
Respondé ÚNICAMENTE con un objeto JSON válido, sin texto adicional, con exactamente estas claves
(usá null si el valor no aparece en la imagen):

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

        $response = Http::withToken(config('services.groq.key'))
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'max_tokens' => 500,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',       'text'      => $prompt],
                        ['type' => 'image_url',  'image_url' => [
                            'url' => "data:{$mimeType};base64,{$base64}",
                        ]],
                    ],
                ]],
            ]);

        // Clean up temp file
        Storage::disk('local')->delete($path);

        $raw = $response->json('choices.0.message.content') ?? '';

        // Extract JSON block if model wraps it in markdown
        if (preg_match('/```(?:json)?\s*([\s\S]+?)\s*```/', $raw, $m)) {
            $raw = $m[1];
        }

        $data = json_decode(trim($raw), true);

        if (!is_array($data)) {
            return response()->json(['error' => 'No se pudo extraer los datos del InBody. Intentá con una imagen más clara.'], 422);
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
            'image'                => 'nullable|image|max:10240',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store("inbody/{$patient->id}", 'public');
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
