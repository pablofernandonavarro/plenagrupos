<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AiDocumentController extends Controller
{
    public function index()
    {
        $documents = AiDocument::orderBy('order')->orderBy('id')->get();
        return view('admin.ai-documents.index', compact('documents'));
    }

    public function create()
    {
        return view('admin.ai-documents.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'source'  => 'nullable|string|max:255',
            'content' => 'required|string',
            'active'  => 'nullable|boolean',
            'order'   => 'nullable|integer|min:0',
        ]);

        AiDocument::create([
            'title'   => $data['title'],
            'source'  => $data['source'] ?? null,
            'content' => $data['content'],
            'active'  => !empty($data['active']),
            'order'   => $data['order'] ?? 0,
        ]);

        // Clear AI analysis cache so next generation uses the new document
        Cache::flush();

        return redirect()->route('admin.ai-documents.index')
            ->with('success', 'Documento agregado. El caché de análisis fue limpiado.');
    }

    public function edit(AiDocument $aiDocument)
    {
        return view('admin.ai-documents.edit', compact('aiDocument'));
    }

    public function update(Request $request, AiDocument $aiDocument)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'source'  => 'nullable|string|max:255',
            'content' => 'required|string',
            'active'  => 'nullable|boolean',
            'order'   => 'nullable|integer|min:0',
        ]);

        $aiDocument->update([
            'title'   => $data['title'],
            'source'  => $data['source'] ?? null,
            'content' => $data['content'],
            'active'  => !empty($data['active']),
            'order'   => $data['order'] ?? 0,
        ]);

        Cache::flush();

        return redirect()->route('admin.ai-documents.index')
            ->with('success', 'Documento actualizado.');
    }

    public function destroy(AiDocument $aiDocument)
    {
        $aiDocument->delete();
        Cache::flush();
        return back()->with('success', 'Documento eliminado.');
    }
}
