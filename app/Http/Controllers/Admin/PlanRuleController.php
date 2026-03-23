<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanRule;
use Illuminate\Http\Request;

class PlanRuleController extends Controller
{
    public function index()
    {
        $plans      = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $groupTypes = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

        // Load all rules indexed by patient_plan.group_type
        $rules = PlanRule::all()->keyBy(fn($r) => $r->patient_plan . '.' . $r->group_type);

        return view('admin.plan-rules.index', compact('plans', 'groupTypes', 'rules'));
    }

    public function save(Request $request)
    {
        $plans      = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];
        $groupTypes = ['descenso', 'mantenimiento', 'mantenimiento_pleno'];

        foreach ($plans as $plan) {
            foreach ($groupTypes as $gt) {
                $limitRaw = $request->input("limit.{$plan}.{$gt}");
                $limit    = ($limitRaw !== null && $limitRaw !== '') ? (int) $limitRaw : null;
                $weekend  = $request->boolean("weekend.{$plan}.{$gt}");

                PlanRule::updateOrCreate(
                    ['patient_plan' => $plan, 'group_type' => $gt],
                    ['weekly_limit' => $limit, 'weekend_unlimited' => $weekend]
                );
            }
        }

        return back()->with('success', 'Reglas de acceso guardadas.');
    }
}
