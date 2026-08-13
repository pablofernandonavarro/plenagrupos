<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DataExportController;
use App\Http\Controllers\Admin\PatientAdherenceController;
use App\Http\Controllers\AppointmentActionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Coordinator;
use App\Http\Controllers\Coordinator\InbodyController as CoordinatorInbodyController;
use App\Http\Controllers\Coordinator\PatientController as CoordinatorPatientController;
use App\Http\Controllers\GroupJoinController;
use App\Http\Controllers\Patient;
use Illuminate\Support\Facades\Route;

// Redirect root based on role
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'coordinator' => redirect()->route('coordinator.dashboard'),
        'medico', 'nutricionista' => redirect()->route('admin.turnos.calendar'),
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

// Debug - REMOVE AFTER TESTING
Route::get('/debug-patient-group/{groupId}/{userId}', function ($groupId, $userId) {
    $pivot = DB::table('group_patient')
        ->where('group_id', $groupId)
        ->where('user_id', $userId)
        ->first();

    $user = \App\Models\User::find($userId);
    $group = \App\Models\Group::find($groupId);

    return response()->json([
        'user' => $user ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email] : null,
        'group' => $group ? ['id' => $group->id, 'name' => $group->name] : null,
        'pivot_exists' => $pivot !== null,
        'pivot_data' => $pivot,
        'active_patients_count' => $group?->patients()->count(),
        'all_patients_count' => $group?->patientsAll()->count(),
    ]);
});

// QR Group Join
Route::get('/grupo/{token}', [GroupJoinController::class, 'show'])->name('group.join');
Route::post('/grupo/{token}', [GroupJoinController::class, 'join'])->name('group.join.post')->middleware('auth');

// Confirmar/cancelar turno desde el link firmado que llega por WhatsApp (sin necesidad de login).
// GET solo muestra la pantalla con el botón — no cambia nada, para que la vista previa que
// WhatsApp genera automáticamente al mandar el mensaje no confirme/cancele el turno sola.
// El paciente tiene que tocar el botón (POST) para que la acción se aplique de verdad.
Route::get('/turnos/{appointment}/confirmar', [AppointmentActionController::class, 'confirmShow'])
    ->name('turnos.public.confirm')->middleware('signed');
Route::post('/turnos/{appointment}/confirmar', [AppointmentActionController::class, 'confirm'])
    ->name('turnos.public.confirm.submit')->middleware('signed');
Route::get('/turnos/{appointment}/cancelar', [AppointmentActionController::class, 'cancelShow'])
    ->name('turnos.public.cancel')->middleware('signed');
Route::post('/turnos/{appointment}/cancelar', [AppointmentActionController::class, 'cancel'])
    ->name('turnos.public.cancel.submit')->middleware('signed');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ayuda', fn() => view('admin.help'))->name('help');

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/grupos', [AnalyticsController::class, 'groups'])->name('groups');
        Route::get('/inbody', [AnalyticsController::class, 'inbody'])->name('inbody');
        Route::get('/cohortes', [AnalyticsController::class, 'cohorts'])->name('cohorts');
    });

    Route::get('/adherencia', [PatientAdherenceController::class, 'index'])->name('adherence.index');

    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('/asistencias', [DataExportController::class, 'attendances'])->name('attendances');
        Route::get('/pesos', [DataExportController::class, 'weights'])->name('weights');
        Route::get('/inbody', [DataExportController::class, 'inbody'])->name('inbody');
        Route::get('/pacientes-por-grupo', [DataExportController::class, 'groupPatients'])->name('group-patients');
    });

    Route::get('/groups/papelera', [Admin\GroupController::class, 'trashed'])->name('groups.trashed');
    Route::resource('groups', Admin\GroupController::class);
    Route::post('/groups/{group}/restore', [Admin\GroupController::class, 'restore'])->name('groups.restore')->withTrashed();
    Route::delete('/groups/{group}/force', [Admin\GroupController::class, 'forceDelete'])->name('groups.force-delete')->withTrashed();
    Route::post('/groups/{group}/toggle', [Admin\GroupController::class, 'toggle'])->name('groups.toggle');
    Route::post('/groups/{group}/reactivate', [Admin\GroupController::class, 'reactivate'])->name('groups.reactivate');
    Route::post('/groups/{group}/close-session', [Admin\GroupController::class, 'closeSession'])->name('groups.close-session');
    Route::get('/groups/{group}/live', [Admin\GroupController::class, 'liveAttendances'])->name('groups.live');
    Route::patch('/groups/{group}/attendances/{attendance}/checkout', [Admin\GroupController::class, 'checkoutAttendance'])->name('groups.attendance.checkout');
    Route::post('/groups/{group}/coordinators', [Admin\GroupController::class, 'addCoordinator'])->name('groups.coordinators.add');
    Route::delete('/groups/{group}/coordinators', [Admin\GroupController::class, 'removeCoordinator'])->name('groups.coordinators.remove');
    Route::post('/groups/{group}/patients', [Admin\GroupController::class, 'addPatient'])->name('groups.patients.add');
    Route::delete('/groups/{group}/patients', [Admin\GroupController::class, 'removePatient'])->name('groups.patients.remove');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/papelera', [Admin\UserController::class, 'trashed'])->name('users.trashed');
    Route::post('/users/{user}/restore', [Admin\UserController::class, 'restore'])->name('users.restore')->withTrashed();
    Route::delete('/users/{user}/force', [Admin\UserController::class, 'forceDelete'])->name('users.force-delete')->withTrashed();
    Route::get('/users/import', [Admin\UserImportController::class, 'show'])->name('users.import');
    Route::post('/users/import', [Admin\UserImportController::class, 'import'])->name('users.import.store');
    Route::get('/users/import/template', [Admin\UserImportController::class, 'template'])->name('users.import.template');
    Route::get('/users/create', [Admin\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [Admin\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/make-admin', [Admin\UserController::class, 'makeAdmin'])->name('users.make-admin');

    Route::resource('ai-documents', Admin\AiDocumentController::class)
        ->except(['show']);

    Route::get('/plan-rules', [Admin\PlanRuleController::class, 'index'])->name('plan-rules.index');
    Route::post('/plan-rules', [Admin\PlanRuleController::class, 'save'])->name('plan-rules.save');

    Route::get('/whatsapp', [Admin\WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/status', [Admin\WhatsAppController::class, 'status'])->name('whatsapp.status');
    Route::get('/whatsapp/qr', [Admin\WhatsAppController::class, 'qr'])->name('whatsapp.qr');
    Route::post('/whatsapp/connect', [Admin\WhatsAppController::class, 'connect'])->name('whatsapp.connect');
    Route::post('/whatsapp/send', [Admin\WhatsAppController::class, 'send'])->name('whatsapp.send');
    Route::delete('/whatsapp/disconnect', [Admin\WhatsAppController::class, 'disconnect'])->name('whatsapp.disconnect');

    Route::get('/whatsapp/plantillas', [Admin\WhatsappTemplateController::class, 'index'])->name('whatsapp.templates.index');
    Route::post('/whatsapp/plantillas', [Admin\WhatsappTemplateController::class, 'save'])->name('whatsapp.templates.save');

    Route::get('/attendances', [Admin\AttendanceController::class, 'index'])->name('attendances.index');
    Route::delete('/attendances/{attendance}', [Admin\AttendanceController::class, 'destroy'])->name('attendances.destroy');

    Route::prefix('turnos')->name('turnos.')->group(function () {
        Route::get('/', [Admin\AppointmentController::class, 'index'])->name('index');
        Route::get('/crear', [Admin\AppointmentController::class, 'create'])->name('create');
        Route::post('/', [Admin\AppointmentController::class, 'store'])->name('store');
        Route::get('/disponibilidad', [Admin\AppointmentController::class, 'availableSlots'])->name('available-slots');
        Route::get('/cumplimiento', [Admin\AppointmentController::class, 'compliance'])->name('compliance');
        Route::get('/{appointment}/editar', [Admin\AppointmentController::class, 'edit'])->name('edit');
        Route::put('/{appointment}', [Admin\AppointmentController::class, 'update'])->name('update');
        Route::delete('/{appointment}', [Admin\AppointmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('profesionales')->name('professionals.')->group(function () {
        Route::get('/', [Admin\ProfessionalController::class, 'index'])->name('index');
        Route::get('/{professional}/horarios', [Admin\ProfessionalController::class, 'edit'])->name('schedule.edit');
        Route::put('/{professional}/horarios', [Admin\ProfessionalController::class, 'updateSchedule'])->name('schedule.update');
        Route::post('/{professional}/ausencias', [Admin\ProfessionalController::class, 'storeUnavailability'])->name('unavailabilities.store');
        Route::delete('/ausencias/{unavailability}', [Admin\ProfessionalController::class, 'destroyUnavailability'])->name('unavailabilities.destroy');
    });

    Route::get('/requisitos-turnos', [Admin\AppointmentRequirementController::class, 'index'])->name('appointment-requirements.index');
    Route::post('/requisitos-turnos', [Admin\AppointmentRequirementController::class, 'save'])->name('appointment-requirements.save');
});

// Calendario de turnos: admin ve todo, médico/nutricionista ven (y aterrizan en) su propia agenda
Route::middleware(['auth', 'role:admin,medico,nutricionista'])->prefix('admin/turnos')->name('admin.turnos.')->group(function () {
    Route::get('/calendario', [Admin\AppointmentController::class, 'calendar'])->name('calendar');
    Route::get('/calendario/eventos', [Admin\AppointmentController::class, 'calendarEvents'])->name('calendar.events');
});

// Coordinator routes
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    Route::get('/dashboard', [Coordinator\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ayuda', fn() => view('coordinator.help'))->name('help');
    Route::get('/grupos/{group}', [Coordinator\DashboardController::class, 'showGroup'])->name('groups.show');
    Route::get('/grupos/{group}/asistencia', [Coordinator\DashboardController::class, 'liveAttendances'])->name('groups.live');
    Route::patch('/grupos/{group}/asistencias/{attendance}/checkout', [Coordinator\DashboardController::class, 'checkoutAttendance'])->name('groups.attendance.checkout');
    Route::post('/grupos/{group}/mantenimiento', [Coordinator\DashboardController::class, 'updateMaintenanceWeight'])->name('groups.maintenance');
    Route::post('/grupos/{group}/toggle', [Coordinator\DashboardController::class, 'toggleGroup'])->name('groups.toggle');
    Route::post('/grupos/{group}/coordinadores', [Coordinator\DashboardController::class, 'addCoordinator'])->name('groups.coordinators.add');
    Route::delete('/grupos/{group}/coordinadores', [Coordinator\DashboardController::class, 'removeCoordinator'])->name('groups.coordinators.remove');
    Route::post('/grupos/{group}/pacientes', [Coordinator\DashboardController::class, 'addPatient'])->name('groups.patients.add');
    Route::delete('/grupos/{group}/pacientes', [Coordinator\DashboardController::class, 'removePatient'])->name('groups.patients.remove');

    Route::get('/perfil', [Coordinator\DashboardController::class, 'profile'])->name('profile');
    Route::post('/perfil', [Coordinator\DashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/pacientes', [CoordinatorPatientController::class, 'index'])->name('patients.index');
    Route::get('/pacientes/{patient}', [CoordinatorPatientController::class, 'show'])->name('patients.show');
    Route::patch('/pacientes/{patient}/fase', [CoordinatorPatientController::class, 'updateFase'])->name('patients.fase');
    Route::patch('/pacientes/{patient}/clinical-profile', [CoordinatorPatientController::class, 'updateClinicalProfile'])->name('patients.clinical-profile');
    Route::patch('/asistencias/{attendance}/notes', [CoordinatorPatientController::class, 'updateAttendanceNotes'])->name('attendances.notes');
    Route::post('/pacientes/{patient}/ai-analysis', [CoordinatorPatientController::class, 'aiAnalysis'])->name('patients.ai-analysis');

    Route::get('/pacientes/{patient}/inbody/crear', [CoordinatorInbodyController::class, 'create'])->name('patients.inbody.create');
    Route::post('/pacientes/{patient}/inbody/extraer', [CoordinatorInbodyController::class, 'extract'])->name('patients.inbody.extract');
    Route::post('/pacientes/{patient}/inbody', [CoordinatorInbodyController::class, 'store'])->name('patients.inbody.store');
    Route::delete('/pacientes/{patient}/inbody/{record}', [CoordinatorInbodyController::class, 'destroy'])->name('patients.inbody.destroy');
});

// Patient routes
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', [Patient\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/ayuda', fn() => view('patient.help'))->name('help');
    Route::get('/perfil', [Patient\DashboardController::class, 'profile'])->name('profile');
    Route::post('/perfil', [Patient\DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/peso/registrar', [Patient\WeightController::class, 'create'])->name('weight.create');
    Route::post('/peso', [Patient\WeightController::class, 'store'])->name('weight.store');
    Route::post('/grupos/{group}/salir', [Patient\DashboardController::class, 'leaveGroup'])->name('groups.leave');
    Route::get('/asistencias/estado', [Patient\DashboardController::class, 'attendanceStatus'])->name('attendances.status');
    Route::get('/inbody', [Patient\InbodyController::class, 'create'])->name('inbody.create');
    Route::post('/inbody/extraer', [Patient\InbodyController::class, 'extract'])->name('inbody.extract');
    Route::post('/inbody', [Patient\InbodyController::class, 'store'])->name('inbody.store');
    Route::get('/inbody/{record}/editar', [Patient\InbodyController::class, 'edit'])->name('inbody.edit');
    Route::put('/inbody/{record}', [Patient\InbodyController::class, 'update'])->name('inbody.update');
    Route::delete('/inbody/{record}', [Patient\InbodyController::class, 'destroy'])->name('inbody.destroy');

    Route::prefix('turnos')->name('turnos.')->group(function () {
        Route::get('/', [Patient\AppointmentController::class, 'index'])->name('index');
        Route::get('/sacar', [Patient\AppointmentController::class, 'create'])->name('create');
        Route::get('/disponibilidad', [Patient\AppointmentController::class, 'availableSlots'])->name('available-slots');
        Route::post('/', [Patient\AppointmentController::class, 'store'])->name('store');
        Route::delete('/{appointment}', [Patient\AppointmentController::class, 'destroy'])->name('destroy');
    });
});
