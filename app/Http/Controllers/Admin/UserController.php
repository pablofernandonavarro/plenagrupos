<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembershipLog;
use App\Models\User;
use App\Rules\WhatsappPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $medicos = User::where('role', 'medico')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest()->get();

        $nutricionistas = User::where('role', 'nutricionista')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest()->get();

        return view('admin.users.index', compact('coordinators', 'patients', 'medicos', 'nutricionistas'));
    }

    public function create(Request $request)
    {
        $role = $request->query('role', 'patient');

        return view('admin.users.create', compact('role'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'phone'          => ['nullable', 'string', 'max:20', new WhatsappPhone],
            'role'           => 'required|in:coordinator,patient,medico,nutricionista',
            'plan'           => 'nullable|in:descenso,mantenimiento,mantenimiento_pleno',
            'plan_start_date'=> 'nullable|date',
            'birth_date'     => 'nullable|date|before:today',
            'gender'         => 'nullable|in:male,female,other',
            'height_cm'      => 'nullable|integer|min:50|max:250',
            'personal_goal'  => 'nullable|string|max:1000',
            'password'       => 'required|min:8|confirmed',
            'avatar'         => 'nullable|image|max:2048',
        ]);

        $user = User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'phone'          => $data['phone'] ?? null,
            'role'           => $data['role'],
            'plan'           => ($data['role'] === 'patient') ? ($data['plan'] ?? null) : null,
            'plan_start_date'=> ($data['role'] === 'patient') ? ($data['plan_start_date'] ?? null) : null,
            'patient_status' => $data['role'] === 'patient' ? 'active' : null,
            'birth_date'     => $data['birth_date'] ?? null,
            'gender'         => $data['gender'] ?? null,
            'height_cm'      => $data['height_cm'] ?? null,
            'personal_goal'  => ($data['role'] === 'patient') ? ($data['personal_goal'] ?? null) : null,
            'password'       => Hash::make($data['password']),
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
            ? $user->patientGroups()->wherePivotNull('left_at')->get()->reject(fn ($g) => $g->id === $user->belonging_group_id)
            : collect();

        return view('admin.users.edit', compact('user', 'groups', 'activeGroupEnrollments'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name'           => 'required|string|max:255',
            'email'          => ['required', 'email', Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'phone'          => ['nullable', 'string', 'max:20', new WhatsappPhone],
            'plan'           => 'nullable|in:descenso,mantenimiento,mantenimiento_pleno',
            'plan_start_date'=> 'nullable|date',
            'ideal_weight'   => 'nullable|numeric|min:0|max:300',
            'peso_piso'      => 'nullable|numeric|min:0|max:300',
            'peso_techo'     => 'nullable|numeric|min:0|max:300',
            'birth_date'     => 'nullable|date|before:today',
            'gender'         => 'nullable|in:male,female,other',
            'height_cm'      => 'nullable|integer|min:50|max:250',
            'personal_goal'  => 'nullable|string|max:1000',
            'password'       => 'nullable|min:8|confirmed',
            'avatar'         => 'nullable|image|max:2048',
        ];

        if ($user->role === 'patient') {
            $rules['patient_status'] = 'required|in:active,pause,exited';
            $rules['patient_status_note'] = 'nullable|string|max:2000';
            $rules['belonging_group_id'] = 'nullable|exists:groups,id';
        }

        $data = $request->validate($rules);

        $user->name           = $data['name'];
        $user->email          = $data['email'];
        $user->phone          = $data['phone'] ?? null;
        $user->plan           = $user->role === 'patient' ? ($data['plan'] ?? null) : null;
        $user->plan_start_date= $user->role === 'patient' ? ($data['plan_start_date'] ?? null) : null;
        $user->ideal_weight   = $data['ideal_weight'] ?? null;
        $user->peso_piso      = $data['peso_piso'] ?? null;
        $user->peso_techo     = $data['peso_techo'] ?? null;
        $user->birth_date     = $data['birth_date'] ?? null;
        $user->gender         = $data['gender'] ?? null;
        $user->height_cm      = $data['height_cm'] ?? null;
        if ($user->role === 'patient') {
            $user->personal_goal = $data['personal_goal'] ?? null;
        }

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

        // El grupo de pertenencia implica estar en el roster de ese grupo:
        // si todavía no está inscripto (o ya salió), lo agregamos/reactivamos.
        if ($user->role === 'patient' && $user->belonging_group_id) {
            $existing = DB::table('group_patient')
                ->where('group_id', $user->belonging_group_id)
                ->where('user_id', $user->id)
                ->first();

            if (! $existing) {
                Group::find($user->belonging_group_id)?->patients()->attach($user->id, [
                    'joined_at' => now(),
                    'join_source' => 'manual',
                ]);
                GroupMembershipLog::create([
                    'group_id' => $user->belonging_group_id,
                    'user_id' => $user->id,
                    'joined_at' => now(),
                    'join_source' => 'manual',
                ]);
            } elseif ($existing->left_at !== null) {
                DB::table('group_patient')
                    ->where('group_id', $user->belonging_group_id)
                    ->where('user_id', $user->id)
                    ->update(['joined_at' => now(), 'left_at' => null, 'join_source' => 'manual']);
                GroupMembershipLog::create([
                    'group_id' => $user->belonging_group_id,
                    'user_id' => $user->id,
                    'joined_at' => now(),
                    'join_source' => 'manual',
                ]);
            }
            // else: ya está activo en ese grupo, nada que hacer
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function makeAdmin(User $user)
    {
        if (! $user->isCoordinator()) {
            return back()->with('error', 'Solo se puede convertir en administrador a un coordinador.');
        }

        $user->role = 'admin';
        $user->save();

        return back()->with('success', "{$user->name} ahora es administrador.");
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'No se puede eliminar un administrador.');
        }
        $user->delete();

        return back()->with('success', 'Usuario movido a la papelera.');
    }

    public function trashed(Request $request)
    {
        $search = $request->input('search');

        $trashedUsers = User::onlyTrashed()
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest('deleted_at')->get();

        return view('admin.users.trashed', compact('trashedUsers'));
    }

    public function restore(User $user)
    {
        $user->restore();

        return redirect()->route('admin.users.trashed')->with('success', 'Usuario restaurado.');
    }

    public function forceDelete(User $user)
    {
        $user->forceDelete();

        return redirect()->route('admin.users.trashed')->with('success', 'Usuario eliminado definitivamente.');
    }
}
