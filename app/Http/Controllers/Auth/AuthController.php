<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\WhatsappPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $redirect = $request->query('redirect');
            if ($redirect) {
                return redirect($redirect);
            }

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['token' => $request->query('session_token')]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:20', new WhatsappPhone],
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'patient',
            'patient_status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $token = $request->query('session_token');
        if ($token) {
            return redirect()->route('session.join', $token);
        }

        return redirect()->route('patient.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(User $user)
    {
        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'coordinator' => redirect()->route('coordinator.dashboard'),
            'medico', 'nutricionista' => redirect()->route('admin.turnos.calendar'),
            default => redirect()->route('patient.dashboard'),
        };
    }
}
