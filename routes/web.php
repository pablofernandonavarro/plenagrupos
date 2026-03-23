<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Coordinator;
use App\Http\Controllers\Patient;
use App\Http\Controllers\GroupJoinController;
use App\Http\Controllers\Coordinator\PatientController as CoordinatorPatientController;
use Illuminate\Support\Facades\Route;

// Redirect root based on role
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'coordinator' => redirect()->route('coordinator.dashboard'),
        default => redirect()->route('patient.dashboard'),
    };
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// QR Group Join
Route::get('/grupo/{token}', [GroupJoinController::class, 'show'])->name('group.join');
Route::post('/grupo/{token}', [GroupJoinController::class, 'join'])->name('group.join.post')->middleware('auth');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('groups', Admin\GroupController::class);
    Route::post('/groups/{group}/toggle', [Admin\GroupController::class, 'toggle'])->name('groups.toggle');
    Route::get('/groups/{group}/live', [Admin\GroupController::class, 'liveAttendances'])->name('groups.live');
    Route::post('/groups/{group}/coordinators', [Admin\GroupController::class, 'addCoordinator'])->name('groups.coordinators.add');
    Route::delete('/groups/{group}/coordinators', [Admin\GroupController::class, 'removeCoordinator'])->name('groups.coordinators.remove');
    Route::post('/groups/{group}/patients', [Admin\GroupController::class, 'addPatient'])->name('groups.patients.add');
    Route::delete('/groups/{group}/patients', [Admin\GroupController::class, 'removePatient'])->name('groups.patients.remove');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    Route::resource('ai-documents', Admin\AiDocumentController::class)
        ->except(['show']);

    Route::get('/plan-rules', [Admin\PlanRuleController::class, 'index'])->name('plan-rules.index');
    Route::post('/plan-rules', [Admin\PlanRuleController::class, 'save'])->name('plan-rules.save');
});

// Coordinator routes
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/dashboard', [Coordinator\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/grupos/{group}', [Coordinator\DashboardController::class, 'showGroup'])->name('groups.show');
    Route::get('/grupos/{group}/asistencia', [Coordinator\DashboardController::class, 'liveAttendances'])->name('groups.live');
    Route::post('/grupos/{group}/mantenimiento', [Coordinator\DashboardController::class, 'updateMaintenanceWeight'])->name('groups.maintenance');
    Route::post('/grupos/{group}/toggle', [Coordinator\DashboardController::class, 'toggleGroup'])->name('groups.toggle');

    Route::get('/perfil', [Coordinator\DashboardController::class, 'profile'])->name('profile');
    Route::post('/perfil', [Coordinator\DashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/pacientes', [CoordinatorPatientController::class, 'index'])->name('patients.index');
    Route::get('/pacientes/{patient}', [CoordinatorPatientController::class, 'show'])->name('patients.show');
    Route::post('/pacientes/{patient}/ai-analysis', [CoordinatorPatientController::class, 'aiAnalysis'])->name('patients.ai-analysis');
});

// Patient routes
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', [Patient\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/perfil', [Patient\DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/peso/registrar', [Patient\WeightController::class, 'create'])->name('weight.create');
    Route::post('/peso', [Patient\WeightController::class, 'store'])->name('weight.store');
});
