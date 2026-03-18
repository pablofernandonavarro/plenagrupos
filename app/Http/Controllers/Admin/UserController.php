<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $coordinators = User::where('role', 'coordinator')
            ->when($search, fn($q) => $q->where(fn($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")
            ))
            ->latest()->get();

        $patients = User::where('role', 'patient')
            ->when($search, fn($q) => $q->where(fn($q) => $q
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:coordinator,patient',
            'password' => 'required|min:8|confirmed',
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'role'     => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', ucfirst($data['role']) . ' creado exitosamente.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'        => 'nullable|string|max:20',
            'ideal_weight' => 'nullable|numeric|min:0|max:300',
            'password'     => 'nullable|min:8|confirmed',
        ]);

        $user->name         = $data['name'];
        $user->email        = $data['email'];
        $user->phone        = $data['phone'] ?? null;
        $user->ideal_weight = $data['ideal_weight'] ?? null;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
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
