<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRequirement;
use Illuminate\Http\Request;

class AppointmentRequirementController extends Controller
{
    private const SPECIALTIES = ['medico', 'nutricionista'];
    private const PLANS = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

    public function index()
    {
        $specialties = self::SPECIALTIES;
        $plans       = self::PLANS;
        $requirements = AppointmentRequirement::all()->keyBy(fn ($r) => $r->patient_plan.'.'.$r->specialty);

        return view('admin.appointment-requirements.index', compact('specialties', 'plans', 'requirements'));
    }

    public function save(Request $request)
    {
        foreach (self::PLANS as $plan) {
            foreach (self::SPECIALTIES as $specialty) {
                $count = max(0, (int) $request->input("required.{$plan}.{$specialty}", 1));

                AppointmentRequirement::updateOrCreate(
                    ['patient_plan' => $plan, 'specialty' => $specialty],
                    ['monthly_required_count' => $count]
                );
            }
        }

        return back()->with('success', 'Requisitos mensuales guardados.');
    }
}
