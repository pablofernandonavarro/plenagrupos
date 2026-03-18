<?php

namespace App\Http\Controllers;

use App\Models\SessionAttendance;
use App\Models\TherapeuticSession;
use Illuminate\Http\Request;

class SessionJoinController extends Controller
{
    public function show(string $token)
    {
        $session = TherapeuticSession::where('qr_token', $token)->with('group')->firstOrFail();

        if (!auth()->check()) {
            return redirect()->route('login', ['redirect' => route('session.join', $token)]);
        }

        $user = auth()->user();

        if (!$user->isPatient()) {
            return redirect()->route('admin.dashboard')->with('info', 'Solo los pacientes pueden unirse a sesiones.');
        }

        // Check if session is active
        if (!$session->isActive()) {
            return view('session.join', ['session' => $session, 'message' => 'Esta sesión está cerrada.', 'alreadyJoined' => false, 'sessionClosed' => true]);
        }

        // Check if already checked in
        $alreadyJoined = SessionAttendance::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyJoined) {
            return view('session.join', compact('session', 'alreadyJoined'));
        }

        return view('session.join', compact('session', 'alreadyJoined'));
    }

    public function join(Request $request, string $token)
    {
        $session = TherapeuticSession::where('qr_token', $token)->firstOrFail();

        if (!auth()->check()) {
            return redirect()->route('login', ['redirect' => route('session.join', $token)]);
        }

        $user = auth()->user();

        if (!$user->isPatient()) {
            return redirect()->route('admin.dashboard');
        }

        if (!$session->isActive()) {
            return back()->with('error', 'Esta sesión está cerrada.');
        }

        SessionAttendance::firstOrCreate([
            'session_id' => $session->id,
            'user_id' => $user->id,
        ], [
            'checked_in_at' => now(),
        ]);

        return redirect()->route('patient.weight.create', ['session' => $session->id])
            ->with('success', '¡Bienvenido! Registra tu peso para continuar.');
    }
}
