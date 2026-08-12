<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\WhatsappPhone;
use App\Services\WahaClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function index(WahaClient $waha)
    {
        $status = $waha->status();

        return view('admin.whatsapp.index', [
            'status' => $status['status'] ?? null,
            'me' => $status['me'] ?? null,
        ]);
    }

    public function status(WahaClient $waha)
    {
        $status = $waha->status();

        return response()->json([
            'status' => $status['status'] ?? null,
            'me' => $status['me'] ?? null,
        ]);
    }

    public function connect(WahaClient $waha)
    {
        $status = $waha->status();

        // FAILED no se recupera con /start (queda pegada ahí para siempre) — hay que recrearla.
        if ($status !== null && $status['status'] === 'FAILED') {
            $waha->deleteSession();
            $status = null;
        }

        if ($status === null) {
            $waha->createSession();
        }

        $waha->startSession();

        return back()->with('success', 'Vinculación iniciada. Escaneá el código QR con WhatsApp.');
    }

    public function qr(WahaClient $waha)
    {
        $response = $waha->qr();

        if (! $response->successful()) {
            abort(404);
        }

        $contentType = $response->header('Content-Type');

        if ($contentType && str_starts_with($contentType, 'image/')) {
            return response($response->body(), 200)->header('Content-Type', $contentType);
        }

        // Fallback: algunas versiones de WAHA devuelven JSON con la imagen en base64.
        $data = $response->json('data') ?? $response->json('value');
        abort_if(! $data, 404);

        return response(base64_decode($data), 200)->header('Content-Type', $response->json('mimetype', 'image/png'));
    }

    public function send(Request $request, WahaClient $waha)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', new WhatsappPhone],
            'text' => 'required|string|max:1000',
        ]);

        try {
            $waha->sendText($data['phone'], $data['text']);
        } catch (RequestException $e) {
            $message = $e->response->json('message')
                ?? $e->response->json('exception.message')
                ?? 'No se pudo enviar el mensaje.';

            return response()->json(['error' => $message], 422);
        }

        return response()->json(['success' => true]);
    }

    public function disconnect(WahaClient $waha)
    {
        $waha->deleteSession();

        return back()->with('success', 'WhatsApp desvinculado.');
    }
}
