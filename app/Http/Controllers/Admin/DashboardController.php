<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'groups' => Group::count(),
            'coordinators' => User::where('role', 'coordinator')->count(),
            'patients' => User::where('role', 'patient')->count(),
            'visits_today' => GroupAttendance::whereDate('attended_at', today())->count(),
        ];

        $recentAttendances = GroupAttendance::with(['user', 'group', 'weightRecord'])
            ->latest('attended_at')
            ->limit(8)
            ->get();

        $groups = Group::with(['patients'])->latest()->get();

        return view('admin.dashboard', compact('stats', 'recentAttendances', 'groups'));
    }
}
