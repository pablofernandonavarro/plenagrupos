@extends('layouts.app')
@section('title', 'Editar ' . ucfirst($user->role))

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar usuario</h1>
            <p class="text-sm text-gray-500">{{ ucfirst($user->role) }} · {{ $user->email }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto de perfil</label>
                <div class="flex items-center gap-4">
                    <x-avatar :user="$user" size="lg" />
                    <div class="flex-1">
                        <input type="file" name="avatar" accept="image/*"
                            class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                        <p class="text-xs text-gray-400 mt-1">Dejá vacío para no cambiar la foto. Máx. 2 MB.</p>
                        @error('avatar')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Opcional">
            </div>

            @if($user->role === 'patient')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
                <div class="flex gap-2">
                    @foreach(['descenso'=>'Descenso','mantenimiento'=>'Mantenimiento','mantenimiento_pleno'=>'Mant. Pleno'] as $val => $label)
                        <div class="relative flex-1">
                            <input type="radio" id="plan-{{ $val }}" name="plan" value="{{ $val }}"
                                class="sr-only peer"
                                {{ old('plan', $user->plan ?? 'descenso') === $val ? 'checked' : '' }}>
                            <label for="plan-{{ $val }}"
                                class="block text-center px-2 py-2 rounded-lg text-sm font-medium border border-gray-300
                                peer-checked:border-teal-600 peer-checked:bg-teal-600 peer-checked:text-white
                                hover:border-teal-400 transition select-none cursor-pointer">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio del plan</label>
                <p class="text-xs text-gray-400 mb-1">Día en que abonó el plan. Los 30 días se cuentan desde ahí.</p>
                <input type="date" name="plan_start_date"
                    value="{{ old('plan_start_date', $user->plan_start_date?->format('Y-m-d')) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('plan_start_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peso ideal (kg)</label>
                <input type="number" step="0.01" min="0" max="300" name="ideal_weight"
                    value="{{ old('ideal_weight', $user->ideal_weight) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Ej: 70.50">
                @error('ideal_weight')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grupo de pertenencia</label>
                <p class="text-xs text-gray-400 mb-1">Grupo principal al que pertenece este paciente.</p>
                <select name="belonging_group_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                    <option value="">— Sin grupo asignado —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ old('belonging_group_id', $user->belonging_group_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
                @error('belonging_group_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4 space-y-3">
                <p class="text-sm font-medium text-gray-800">Estado en el programa</p>
                <p class="text-xs text-gray-500">Sirve para cohortes y reportes. «Egreso» excluye al paciente de las métricas de retención.</p>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Estado</label>
                    <select name="patient_status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                        <option value="active" {{ old('patient_status', $user->patient_status ?? 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="pause" {{ old('patient_status', $user->patient_status ?? 'active') === 'pause' ? 'selected' : '' }}>Pausa</option>
                        <option value="exited" {{ old('patient_status', $user->patient_status ?? 'active') === 'exited' ? 'selected' : '' }}>Egreso</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Nota (motivo de pausa/egreso, opcional)</label>
                    <textarea name="patient_status_note" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                        placeholder="Ej.: derivado a otro centro, viaje prolongado…">{{ old('patient_status_note', $user->patient_status_note) }}</textarea>
                </div>
                @if($user->patient_status_at)
                    <p class="text-xs text-gray-400">Último cambio de estado: {{ $user->patient_status_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rango de mantenimiento (kg)</label>
                <p class="text-xs text-gray-400 mb-2">Peso mínimo y máximo aceptable para este paciente.</p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Piso</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_piso"
                            value="{{ old('peso_piso', $user->peso_piso) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 68.00">
                        @error('peso_piso')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Techo</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_techo"
                            value="{{ old('peso_techo', $user->peso_techo) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 72.00">
                        @error('peso_techo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            @endif

            <div class="border-t pt-4">
                <p class="text-sm font-medium text-gray-700 mb-3">Cambiar contraseña <span class="text-gray-400 font-normal">(dejar vacío para mantener la actual)</span></p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Nueva contraseña</label>
                        <input type="password" name="password"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Mínimo 8 caracteres">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Repetir contraseña">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2.5 text-sm">
                    Cancelar
                </a>
            </div>
        </form>

        @if($user->role === 'patient')
        <div class="border-t pt-4 mt-4">
            <p class="text-sm font-medium text-gray-700 mb-1">Grupos activos</p>
            <p class="text-xs text-gray-400 mb-3">Grupos en los que el paciente está inscripto actualmente.</p>
            @if($activeGroupEnrollments->isNotEmpty())
                <div class="space-y-2">
                    @foreach($activeGroupEnrollments as $g)
                    <div class="flex items-center justify-between gap-2 bg-gray-50 rounded-lg px-3 py-2">
                        <span class="text-sm text-gray-700">{{ $g->name }}</span>
                        <form action="{{ route('admin.groups.patients.remove', $g) }}" method="POST"
                              onsubmit="return confirm('¿Remover a {{ $user->name }} del grupo «{{ $g->name }}»?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition">
                                Remover
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">Sin grupos activos.</p>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
