<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfessionalUnavailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfessionalController extends Controller
{
    private const DAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    public function index(Request $request)
    {
        $search = $request->input('search');

        $medicos = User::where('role', 'medico')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")))
            ->withCount('professionalSchedules')
            ->latest()->get();

        $nutricionistas = User::where('role', 'nutricionista')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")))
            ->withCount('professionalSchedules')
            ->latest()->get();

        return view('admin.profesionales.index', compact('medicos', 'nutricionistas'));
    }

    public function edit(User $professional)
    {
        abort_unless($professional->isProfessional(), 404);

        $schedules = $professional->professionalSchedules()
            ->orderByRaw("FIELD(day_of_week, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo')")
            ->orderBy('start_time')
            ->get();

        $unavailabilities = $professional->professionalUnavailabilities()
            ->orderByDesc('start_date')
            ->get();

        $days = self::DAYS;

        return view('admin.profesionales.schedule-edit', compact('professional', 'schedules', 'unavailabilities', 'days'));
    }

    public function updateSchedule(Request $request, User $professional)
    {
        abort_unless($professional->isProfessional(), 404);

        $data = $request->validate([
            'blocks' => 'array',
            'blocks.*.day_of_week' => 'required|in:' . implode(',', self::DAYS),
            'blocks.*.start_time' => 'required|date_format:H:i',
            'blocks.*.end_time' => 'required|date_format:H:i|after:blocks.*.start_time',
            'blocks.*.slot_duration_minutes' => 'required|integer|min:5|max:240',
        ]);

        DB::transaction(function () use ($professional, $data) {
            $professional->professionalSchedules()->delete();

            foreach ($data['blocks'] ?? [] as $block) {
                $professional->professionalSchedules()->create([
                    'day_of_week' => $block['day_of_week'],
                    'start_time' => $block['start_time'],
                    'end_time' => $block['end_time'],
                    'slot_duration_minutes' => $block['slot_duration_minutes'],
                    'active' => true,
                ]);
            }
        });

        return back()->with('success', 'Horario actualizado correctamente.');
    }

    public function storeUnavailability(Request $request, User $professional)
    {
        abort_unless($professional->isProfessional(), 404);

        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
        ]);

        $professional->professionalUnavailabilities()->create($data);

        return back()->with('success', 'Ausencia registrada.');
    }

    public function destroyUnavailability(ProfessionalUnavailability $unavailability)
    {
        $unavailability->delete();

        return back()->with('success', 'Ausencia eliminada.');
    }
}
