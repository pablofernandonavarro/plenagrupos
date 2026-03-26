<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $coordinators = User::where('role', 'coordinator')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest()->get();

        $patients = User::where('role', 'patient')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest()->get();

        return view('admin.users.index', compact('coordinators', 'patients'));
    }

    public function create(Request $request)
    {
        $role = $request->query('role', 'patient');

        return view('admin.users.create', compact('role'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:coordinator,patient',
            'plan' => 'nullable|in:descenso,mantenimiento,mantenimiento_pleno',
            'plan_start_date' => 'nullable|date',
            'password' => 'required|min:8|confirmed',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'plan' => ($data['role'] === 'patient') ? ($data['plan'] ?? null) : null,
            'plan_start_date' => ($data['role'] === 'patient') ? ($data['plan_start_date'] ?? null) : null,
            'patient_status' => $data['role'] === 'patient' ? 'active' : null,
            'password' => Hash::make($data['password']),
        ]);

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        return redirect()->route('admin.users.index')->with('success', ucfirst($data['role']).' creado exitosamente.');
    }

    public function edit(User $user)
    {
        $groups = Group::orderBy('name')->get();
        $activeGroupEnrollments = $user->role === 'patient'
            ? $user->patientGroups()->wherePivotNull('left_at')->get()
            : collect();

        return view('admin.users.edit', compact('user', 'groups', 'activeGroupEnrollments'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'plan' => 'nullable|in:descenso,mantenimiento,mantenimiento_pleno',
            'plan_start_date' => 'nullable|date',
            'ideal_weight' => 'nullable|numeric|min:0|max:300',
            'peso_piso' => 'nullable|numeric|min:0|max:300',
            'peso_techo' => 'nullable|numeric|min:0|max:300',
            'password' => 'nullable|min:8|confirmed',
            'avatar' => 'nullable|image|max:2048',
        ];

        if ($user->role === 'patient') {
            $rules['patient_status'] = 'required|in:active,pause,exited';
            $rules['patient_status_note'] = 'nullable|string|max:2000';
            $rules['belonging_group_id'] = 'nullable|exists:groups,id';
        }

        $data = $request->validate($rules);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->plan = $user->role === 'patient' ? ($data['plan'] ?? null) : null;
        $user->plan_start_date = $user->role === 'patient' ? ($data['plan_start_date'] ?? null) : null;
        $user->ideal_weight = $data['ideal_weight'] ?? null;
        $user->peso_piso = $data['peso_piso'] ?? null;
        $user->peso_techo = $data['peso_techo'] ?? null;

        if ($user->role === 'patient') {
            $newStatus = $data['patient_status'];
            if ($newStatus !== $user->patient_status) {
                $user->patient_status_at = now();
            }
            $user->patient_status = $newStatus;
            $user->patient_status_note = $data['patient_status_note'] ?? null;
            $user->belonging_group_id = $data['belonging_group_id'] ?? null;
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'No se puede eliminar un administrador.');
        }
        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }
}
