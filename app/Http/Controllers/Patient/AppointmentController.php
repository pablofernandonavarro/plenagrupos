<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentWhatsapp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $upcoming = $user->appointmentsAsPatient()
            ->with('professional')
            ->where('starts_at', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at')
            ->get();

        $history = $user->appointmentsAsPatient()
            ->with('professional')
            ->where(fn ($q) => $q->where('starts_at', '<', now())->orWhere('status', 'cancelled'))
            ->orderByDesc('starts_at')
            ->paginate(10)
            ->withQueryString();

        $medicoState = $user->monthlyTurnoState('medico');
        $nutriState = $user->monthlyTurnoState('nutricionista');

        return view('patient.turnos.index', compact('upcoming', 'history', 'medicoState', 'nutriState'));
    }

    public function create(Request $request)
    {
        $specialty = $request->query('specialty', 'medico');
        abort_unless(in_array($specialty, ['medico', 'nutricionista'], true), 404);

        $professionals = User::where('role', $specialty)->orderBy('name')->get();

        return view('patient.turnos.create', compact('specialty', 'professionals'));
    }

    public function availableSlots(Request $request)
    {
        $data = $request->validate([
            'professional_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        $professional = User::findOrFail($data['professional_id']);
        abort_unless($professional->isProfessional(), 404);

        $slots = Appointment::availableSlotsFor($professional, Carbon::parse($data['date']))
            ->map(fn ($s) => $s->format('H:i'))
            ->values();

        return response()->json($slots);
    }

    public function store(Request $request, AppointmentWhatsapp $notifier)
    {
        $data = $request->validate([
            'professional_id' => 'required|exists:users,id',
            'starts_at' => 'required|date',
        ]);

        $professional = User::findOrFail($data['professional_id']);
        abort_unless($professional->isProfessional(), 422);

        try {
            $appointment = Appointment::bookSlot(auth()->user(), $professional, Carbon::parse($data['starts_at']), 'patient');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $notifier->notifyBooked($appointment);

        return redirect()->route('patient.turnos.index')->with('success', 'Turno reservado correctamente.');
    }

    public function destroy(Appointment $appointment, AppointmentWhatsapp $notifier)
    {
        abort_if($appointment->patient_id !== auth()->id(), 403);

        $appointment->update(['status' => 'cancelled']);
        $notifier->notifyCancelled($appointment);

        return back()->with('success', 'Turno cancelado.');
    }
}
