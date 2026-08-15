<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRequirement;
use Illuminate\Http\Request;

class AppointmentRequirementController extends Controller
{
    private const SPECIALTIES = ['medico', 'nutricionista'];
    private const PLANS = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

    // Planes con un único cupo compartido entre especialidades (fila specialty='cualquiera')
    // en vez de un requisito independiente por especialidad.
    private const COMBINED_PLANS = ['mantenimiento_pleno'];

    public function index()
    {
        $specialties = self::SPECIALTIES;
        $plans       = self::PLANS;
        $combinedPlans = self::COMBINED_PLANS;
        $requirements = AppointmentRequirement::all()->keyBy(fn ($r) => $r->patient_plan.'.'.$r->specialty);

        return view('admin.appointment-requirements.index', compact('specialties', 'plans', 'combinedPlans', 'requirements'));
    }

    public function save(Request $request)
    {
        foreach (self::PLANS as $plan) {
            if (in_array($plan, self::COMBINED_PLANS, true)) {
                $count = max(0, (int) $request->input("required.{$plan}.cualquiera", 1));
                $cycleDays = max(1, (int) $request->input("cycle.{$plan}.cualquiera", 60));

                AppointmentRequirement::updateOrCreate(
                    ['patient_plan' => $plan, 'specialty' => 'cualquiera'],
                    ['monthly_required_count' => $count, 'cycle_days' => $cycleDays]
                );

                continue;
            }

            foreach (self::SPECIALTIES as $specialty) {
                $count = max(0, (int) $request->input("required.{$plan}.{$specialty}", 1));
                $cycleDays = max(1, (int) $request->input("cycle.{$plan}.{$specialty}", 30));

                AppointmentRequirement::updateOrCreate(
                    ['patient_plan' => $plan, 'specialty' => $specialty],
                    ['monthly_required_count' => $count, 'cycle_days' => $cycleDays]
                );
            }
        }

        return back()->with('success', 'Requisitos de turnos guardados.');
    }
}
