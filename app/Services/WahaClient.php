<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente delgado para la API de WAHA (WhatsApp HTTP API).
 * Cada app conectada a esta instancia de WAHA usa un nombre de sesión propio.
 */
class WahaClient
{
    protected function client(): PendingRequest
    {
        return Http::withHeaders(['X-Api-Key' => config('services.waha.key')])
            ->baseUrl(rtrim(config('services.waha.url'), '/'))
            ->timeout(15);
    }

    public function session(): string
    {
        return config('services.waha.session');
    }

    /** null si la sesión no existe todavía (nunca se vinculó). */
    public function status(): ?array
    {
        $response = $this->client()->get("/sessions/{$this->session()}");

        if ($response->status() === 404) {
            return null;
        }

        return $response->throw()->json();
    }

    public function createSession(): array
    {
        return $this->client()->post('/sessions', [
            'name' => $this->session(),
            'engine' => 'GOWS',
        ])->throw()->json();
    }

    /** Crear una sesión la deja en STOPPED; hace falta arrancarla para que pase a SCAN_QR_CODE. */
    public function startSession(): array
    {
        return $this->client()->post("/sessions/{$this->session()}/start")->throw()->json();
    }

    public function qr(): Response
    {
        return $this->client()
            ->withHeaders(['Accept' => 'image/png'])
            ->get("/{$this->session()}/auth/qr");
    }

    public function deleteSession(): bool
    {
        return $this->client()->delete("/sessions/{$this->session()}")->successful();
    }

    /** $phone puede venir con espacios/guiones/+; acá se limpia a solo dígitos para armar el chatId. */
    public function sendText(string $phone, string $text): array
    {
        $digits = preg_replace('/\D/', '', $phone);

        return $this->client()->post('/sendText', [
            'session' => $this->session(),
            'chatId' => "{$digits}@c.us",
            'text' => $text,
        ])->throw()->json();
    }
}
