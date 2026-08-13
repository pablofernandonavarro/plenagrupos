<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $specialty = $request->input('specialty');
        $professionalId = $request->input('professional_id');

        $appointments = Appointment::with(['patient', 'professional'])
            ->when($specialty, fn ($q) => $q->where('specialty', $specialty))
            ->when($professionalId, fn ($q) => $q->where('professional_id', $professionalId))
            ->latest('starts_at')
            ->paginate(20)
            ->withQueryString();

        $professionals = User::whereIn('role', ['medico', 'nutricionista'])->orderBy('name')->get();

        return view('admin.turnos.index', compact('appointments', 'professionals', 'specialty', 'professionalId'));
    }

    public function calendar()
    {
        return view('admin.turnos.calendar');
    }

    public function calendarEvents(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $appointments = Appointment::with(['patient', 'professional'])
            ->where('status', '!=', 'cancelled')
            ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
            ->get();

        $colors = ['medico' => '#2563eb', 'nutricionista' => '#09cda6'];

        return response()->json($appointments->map(fn ($a) => [
            'id' => $a->id,
            'title' => ($a->patient->name ?? '—') . ' — ' . ($a->professional->name ?? '—'),
            'start' => $a->starts_at->toIso8601String(),
            'end' => $a->ends_at->toIso8601String(),
            'color' => $colors[$a->specialty] ?? '#6b7280',
            'url' => route('admin.turnos.edit', $a),
        ]));
    }

    public function create()
    {
        $patients = User::where('role', 'patient')->orderBy('name')->get();
        $professionals = User::whereIn('role', ['medico', 'nutricionista'])->orderBy('name')->get();

        return view('admin.turnos.create', compact('patients', 'professionals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'professional_id' => 'required|exists:users,id',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $patient = User::findOrFail($data['patient_id']);
        $professional = User::findOrFail($data['professional_id']);

        abort_unless($patient->isPatient(), 422, 'El paciente seleccionado no es válido.');
        abort_unless($professional->isProfessional(), 422, 'El profesional seleccionado no es válido.');

        try {
            Appointment::bookSlot($patient, $professional, Carbon::parse($data['starts_at']), 'admin', $data['notes'] ?? null);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.turnos.index')->with('success', 'Turno creado correctamente.');
    }

    public function edit(Appointment $appointment)
    {
        $appointment->load(['patient', 'professional']);

        return view('admin.turnos.edit', compact('appointment'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled,no_show',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointment->update($data);

        return back()->with('success', 'Turno actualizado.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->update(['status' => 'cancelled']);

        return back()->with('success', 'Turno cancelado.');
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

    public function compliance()
    {
        $patients = User::where('role', 'patient')
            ->where('patient_status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => (object) [
                'user' => $p,
                'medico_done' => $p->hasCompletedMonthlyRequirement('medico'),
                'nutricionista_done' => $p->hasCompletedMonthlyRequirement('nutricionista'),
            ]);

        return view('admin.turnos.compliance', compact('patients'));
    }
}
