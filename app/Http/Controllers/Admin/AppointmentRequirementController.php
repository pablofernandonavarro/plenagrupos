<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppointmentRequirement;
use Illuminate\Http\Request;

class AppointmentRequirementController extends Controller
{
    private const SPECIALTIES = ['medico', 'nutricionista'];

    public function index()
    {
        $specialties = self::SPECIALTIES;
        $requirements = AppointmentRequirement::all()->keyBy('specialty');

        return view('admin.appointment-requirements.index', compact('specialties', 'requirements'));
    }

    public function save(Request $request)
    {
        foreach (self::SPECIALTIES as $specialty) {
            $count = max(0, (int) $request->input("required.{$specialty}", 1));

            AppointmentRequirement::updateOrCreate(
                ['specialty' => $specialty],
                ['monthly_required_count' => $count]
            );
        }

        return back()->with('success', 'Requisitos mensuales guardados.');
    }
}
