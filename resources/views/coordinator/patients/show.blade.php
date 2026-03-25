@extends('layouts.app')
@section('title', $patient->name)

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start gap-3">
        <a href="{{ route('coordinator.patients.index') }}" class="mt-1 text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex items-start gap-3 min-w-0 flex-1">
            <x-avatar :user="$patient" size="md" />
            <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $patient->name }}</h1>
                @php
                    $piso = $patient->peso_piso; $techo = $patient->peso_techo;
                    if ($lastWeight && $techo && $lastWeight > $techo) $badge = ['↑ Sobre techo', 'bg-red-100 text-red-600'];
                    elseif ($lastWeight && $piso && $lastWeight < $piso) $badge = ['↓ Bajo piso', 'bg-blue-100 text-blue-600'];
                    elseif ($lastWeight && ($piso || $techo)) $badge = ['✓ En rango', 'bg-green-100 text-green-700'];
                    else $badge = null;
                @endphp
                @if($badge)
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-400 mt-0.5 truncate">
                {{ $patient->email }}@if($patient->phone) · {{ $patient->phone }}@endif
            </p>
            @if($groups->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach($groups as $g)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $g->status === 'active' ? 'bg-green-100 text-green-700' : ($g->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ $g->name }}
                        </span>
                        @if($g->started_at)
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $g->started_at->format('d/m/Y · H:i') }}@if($g->ended_at) → {{ $g->started_at->isSameDay($g->ended_at) ? $g->ended_at->format('H:i') : $g->ended_at->format('d/m/Y · H:i') }}@endif
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
            </div>
        </div>
    </div>

    {{-- Plan vs fase actual --}}
    @php
        $planLabels = ['descenso' => 'Descenso', 'mantenimiento' => 'Mantenimiento', 'mantenimiento_pleno' => 'Mantenimiento Pleno'];
        $planColors = ['descenso' => 'bg-blue-100 text-blue-700', 'mantenimiento' => 'bg-green-100 text-green-700', 'mantenimiento_pleno' => 'bg-purple-100 text-purple-700'];
        $faseEfectiva = $patient->faseEfectiva();
        $hayConflicto = $patient->fase_actual && $patient->fase_actual !== $patient->plan;
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex flex-wrap items-center gap-3 justify-between">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Billing plan --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Plan contratado:</span>
                    @if($patient->plan)
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $planColors[$patient->plan] ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $planLabels[$patient->plan] ?? $patient->plan }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Sin plan</span>
                    @endif
                </div>

                @if($hayConflicto)
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l4 10H7l4-10zm0 0V4"/>
                    </svg>
                @endif

                {{-- Active clinical phase --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Fase actual:</span>
                    @if($patient->fase_actual)
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $planColors[$patient->fase_actual] ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $planLabels[$patient->fase_actual] ?? $patient->fase_actual }}
                        </span>
                    @else
                        <span class="text-xs text-gray-400 italic">Igual al plan</span>
                    @endif
                </div>
            </div>

            {{-- Quick change button --}}
            <button onclick="document.getElementById('fase-panel').classList.toggle('hidden')"
                class="text-xs text-indigo-600 hover:underline shrink-0">
                Cambiar fase
            </button>
        </div>
        <p class="text-xs text-gray-400 mt-2 leading-relaxed">
            Los <strong class="font-medium text-gray-600">límites de asistencia</strong> (por tipo de grupo y ciclo) se aplican según la <strong class="font-medium text-gray-600">fase efectiva</strong>: la fase clínica si la definís acá, o el plan contratado si elegís «Usar plan». El ciclo de facturación sigue el plan.
        </p>

        {{-- Inline fase change form --}}
        <div id="fase-panel" class="hidden mt-4 pt-4 border-t border-gray-100">
            <form method="POST" action="{{ route('coordinator.patients.fase', $patient) }}" class="flex flex-wrap items-center gap-3">
                @csrf @method('PATCH')
                <div class="text-xs text-gray-500 w-full space-y-2 leading-relaxed">
                    <p>
                        Las reglas de límites (matriz en administración) se buscan por <strong class="text-gray-700">fase efectiva</strong> —la fase clínica que elijas o, si usás «Usar plan», el plan contratado.
                    </p>
                    <p>
                        Cambiar la fase clínica <strong class="text-gray-700">no modifica el plan de facturación</strong> ni las fechas de ciclo, pero <strong class="text-gray-700">sí puede cambiar los topes de asistencia</strong> si difieren entre filas de la matriz.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 flex-1">
                    @foreach(['descenso' => 'Descenso', 'mantenimiento' => 'Mantenimiento', 'mantenimiento_pleno' => 'Mantenimiento Pleno'] as $val => $lbl)
                    <label class="flex items-center gap-2 cursor-pointer border rounded-lg px-3 py-2 text-sm transition
                        {{ $faseEfectiva === $val ? 'border-indigo-400 bg-indigo-50 text-indigo-700 font-semibold' : 'border-gray-200 text-gray-600 hover:border-indigo-300' }}">
                        <input type="radio" name="fase_actual" value="{{ $val }}" class="hidden"
                            {{ $faseEfectiva === $val ? 'checked' : '' }}>
                        {{ $lbl }}
                    </label>
                    @endforeach
                    <label class="flex items-center gap-2 cursor-pointer border rounded-lg px-3 py-2 text-sm transition
                        {{ !$patient->fase_actual ? 'border-gray-400 bg-gray-50 text-gray-700 font-semibold' : 'border-gray-200 text-gray-500 hover:border-gray-300' }}">
                        <input type="radio" name="fase_actual" value="" class="hidden"
                            {{ !$patient->fase_actual ? 'checked' : '' }}>
                        Usar plan ({{ $planLabels[$patient->plan] ?? 'sin plan' }})
                    </label>
                </div>
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                    Guardar
                </button>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-teal-600">{{ $lastWeight ? $lastWeight . ' kg' : '—' }}</p>
            <p class="text-xs text-gray-400 mt-1">Peso actual</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @if($totalChange !== null)
                <p class="text-2xl font-bold {{ $totalChange < 0 ? 'text-green-600' : ($totalChange > 0 ? 'text-red-500' : 'text-gray-400') }}">
                    {{ $totalChange > 0 ? '+' : '' }}{{ $totalChange }} kg
                </p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">Pérdida total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @if($attendanceRate !== null)
                <p class="text-2xl font-bold {{ $attendanceRate >= 75 ? 'text-blue-600' : ($attendanceRate >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                    {{ $attendanceRate }}%
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $attendedSessions }}/{{ $totalSessions }} sesiones</p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
                <p class="text-xs text-gray-400 mt-1">Asistencia</p>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @php $hrs = intdiv($totalMinutes, 60); $min = $totalMinutes % 60; @endphp
            <p class="text-2xl font-bold text-purple-600">{{ $hrs > 0 ? $hrs.'h ' : '' }}{{ $min }}m</p>
            <p class="text-xs text-gray-400 mt-1">En grupos</p>
        </div>
    </div>

    {{-- Trend + range banner --}}
    @if($weightRecords->count() >= 2)
    @php
        $absT = abs($trend);
        if ($trend < -0.5)      { $tc='text-blue-600';  $ti='↓↓'; $tt='Pérdida acelerada ('  .round($absT,2).' kg/ses.)'; $ts='bg-blue-50 border-blue-200'; }
        elseif ($trend < -0.05) { $tc='text-green-600'; $ti='↓';  $tt='Bajando — ritmo saludable ('.round($absT,2).' kg/ses.)'; $ts='bg-green-50 border-green-200'; }
        elseif ($trend < 0.05)  { $tc='text-gray-500';  $ti='→';  $tt='Peso estable';              $ts='bg-gray-50 border-gray-200'; }
        elseif ($trend < 0.3)   { $tc='text-yellow-600';$ti='↑';  $tt='Leve aumento — monitorear'; $ts='bg-yellow-50 border-yellow-200'; }
        else                    { $tc='text-red-500';   $ti='↑↑'; $tt='Aumento sostenido — requiere seguimiento'; $ts='bg-red-50 border-red-200'; }
    @endphp
    <div class="rounded-xl border px-4 py-3 flex items-center gap-3 {{ $ts }}">
        <span class="text-2xl font-bold {{ $tc }} shrink-0">{{ $ti }}</span>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-800">Tendencia de peso</p>
            <p class="text-xs {{ $tc }}">{{ $tt }}</p>
        </div>
        @if($inRange !== null)
            @php $piso = $patient->peso_piso; $techo = $patient->peso_techo; @endphp
            <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full
                {{ $inRange ? 'bg-green-100 text-green-700' : ($lastWeight > $techo ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700') }}">
                {{ $inRange ? '✓ En rango' : ($lastWeight > $techo ? '↑ Sobre techo' : '↓ Bajo piso') }}
            </span>
        @endif
    </div>
    @endif

    {{-- Progress toward ideal --}}
    @if($progressPct !== null)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex justify-between items-center mb-2">
            <p class="text-sm font-semibold text-gray-700">Progreso hacia peso ideal</p>
            <span class="text-sm font-bold text-teal-600">{{ $progressPct }}%</span>
        </div>
        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-teal-500 rounded-full" style="width:{{ $progressPct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-400 mt-1.5">
            <span>Inicial: {{ $firstWeight }} kg</span>
            <span>Ideal: {{ $patient->ideal_weight }} kg</span>
        </div>
    </div>
    @endif

    {{-- Weight range info --}}
    @if($patient->ideal_weight || $piso || $techo)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex flex-wrap gap-4 sm:gap-8 text-sm">
            @if($patient->ideal_weight)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Peso ideal</p>
                    <p class="font-semibold text-gray-700">{{ $patient->ideal_weight }} kg</p>
                </div>
            @endif
            @if($piso)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Piso</p>
                    <p class="font-semibold text-gray-700">{{ $piso }} kg</p>
                </div>
            @endif
            @if($techo)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Techo</p>
                    <p class="font-semibold text-gray-700">{{ $techo }} kg</p>
                </div>
            @endif
            @if($firstWeight)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Peso inicial</p>
                    <p class="font-semibold text-gray-700">{{ $firstWeight }} kg</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Weight chart --}}
    @if($weightRecords->count() > 1)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Evolución del peso</h2>
        </div>
        <div class="px-4 py-4" style="position:relative; height:240px;">
            <canvas id="weightChart"></canvas>
        </div>
    </div>
    @endif

    {{-- InBody --}}
    @php $latestInbody = $patient->inbodyRecords()->orderByDesc('test_date')->first(); @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">InBody</h2>
                <p class="text-xs text-gray-400 mt-0.5">Composición corporal por bioimpedancia</p>
            </div>
            <a href="{{ route('coordinator.patients.inbody.create', $patient) }}"
               class="flex items-center gap-1.5 text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo
            </a>
        </div>
        @if($latestInbody)
        <div class="px-5 py-4 space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-400">Último estudio: <strong class="text-gray-600">{{ $latestInbody->test_date->format('d/m/Y') }}</strong></p>
                @if($latestInbody->inbody_score)
                    <span class="text-sm font-bold px-3 py-1 rounded-full
                        {{ $latestInbody->inbody_score >= 80 ? 'bg-green-100 text-green-700' : ($latestInbody->inbody_score >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">
                        Score {{ $latestInbody->inbody_score }}
                    </span>
                @endif
            </div>

            {{-- Body composition bar --}}
            @if($latestInbody->weight && $latestInbody->body_fat_mass && $latestInbody->skeletal_muscle_mass)
            @php
                $tbw  = $latestInbody->total_body_water ?? 0;
                $prot = $latestInbody->proteins ?? 0;
                $min  = $latestInbody->minerals ?? 0;
                $fat  = $latestInbody->body_fat_mass;
                $total = $latestInbody->weight;
                $leanPct  = $total > 0 ? round(($total - $fat) / $total * 100) : 0;
                $fatPct   = $total > 0 ? round($fat / $total * 100) : 0;
            @endphp
            <div>
                <div class="flex h-4 rounded-full overflow-hidden text-xs">
                    <div class="bg-blue-400" style="width:{{ $leanPct }}%" title="Masa magra {{ $leanPct }}%"></div>
                    <div class="bg-orange-300" style="width:{{ $fatPct }}%" title="Grasa {{ $fatPct }}%"></div>
                </div>
                <div class="flex gap-4 mt-1.5 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400 inline-block"></span>Masa magra {{ 100 - $fatPct }}%</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-300 inline-block"></span>Grasa {{ $fatPct }}%</span>
                </div>
            </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                @foreach([
                    ['Peso',         $latestInbody->weight          ? $latestInbody->weight . ' kg'   : null, 'text-teal-600'],
                    ['Músculo',      $latestInbody->skeletal_muscle_mass ? $latestInbody->skeletal_muscle_mass . ' kg' : null, 'text-blue-600'],
                    ['Grasa',        $latestInbody->body_fat_percentage ? $latestInbody->body_fat_percentage . '%' : null, 'text-orange-500'],
                    ['IMC',          $latestInbody->bmi              ? $latestInbody->bmi               : null, 'text-gray-700'],
                    ['Visceral',     $latestInbody->visceral_fat_level ? $latestInbody->visceral_fat_level : null, 'text-purple-600'],
                    ['Metabolismo',  $latestInbody->basal_metabolic_rate ? $latestInbody->basal_metabolic_rate . ' kcal' : null, 'text-gray-700'],
                ] as [$lbl, $val, $col])
                @if($val)
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-base font-bold {{ $col }}">{{ $val }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $lbl }}</p>
                </div>
                @endif
                @endforeach
            </div>

            @php $totalInbody = $patient->inbodyRecords()->count(); @endphp
            @if($totalInbody > 1)
            <a href="{{ route('coordinator.patients.inbody.create', $patient) }}"
               class="block text-center text-xs text-indigo-600 hover:underline">
                Ver todos los registros ({{ $totalInbody }})
            </a>
            @endif
        </div>
        @else
        <div class="px-5 py-8 text-center">
            <p class="text-sm text-gray-400">Sin registros InBody.</p>
            <a href="{{ route('coordinator.patients.inbody.create', $patient) }}"
               class="inline-block mt-2 text-xs text-indigo-600 hover:underline">
                Subir primer estudio
            </a>
        </div>
        @endif
    </div>

    {{-- AI Clinical Analysis --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-gray-800">Análisis clínico IA</h2>
                <p class="text-xs text-gray-400 mt-0.5">Generado por LLaMA 3.3 · Se actualiza una vez por día</p>
            </div>
            <button id="btn-ai"
                onclick="loadAiAnalysis(true)"
                class="flex items-center gap-1.5 text-xs bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Generar
            </button>
        </div>
        <div class="px-5 py-4">
            <p id="ai-text" class="text-sm text-gray-400 italic">
                Presioná "Generar" para obtener un análisis clínico basado en los datos del paciente.
            </p>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Trazabilidad</h2>
            <p class="text-xs text-gray-400 mt-0.5">Asistencias y pesos registrados por sesión</p>
        </div>

        @forelse($timelineWithChange as $entry)
            @php
                $w = $entry['weight'];
                $ch = $entry['change'];
            @endphp
            <div class="flex gap-4 px-5 py-4 border-b border-gray-50 last:border-0">
                {{-- Timeline dot --}}
                <div class="flex flex-col items-center pt-1 shrink-0">
                    <div class="w-3 h-3 rounded-full {{ $w ? 'bg-teal-500' : 'bg-gray-200' }} ring-2 ring-white ring-offset-1"></div>
                    @if(!$loop->last)
                        <div class="w-px flex-1 bg-gray-100 mt-1"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-1 pb-1">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $entry['group_name'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $entry['date']->format('d/m/Y · H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($ch !== null)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    {{ $ch < 0 ? 'bg-green-100 text-green-700' : ($ch > 0 ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-500') }}">
                                    {{ $ch > 0 ? '↑ +' : ($ch < 0 ? '↓ ' : '= ') }}{{ $ch }} kg
                                </span>
                            @endif
                            @if($w)
                                <span class="text-base font-bold text-teal-600">{{ $w }} kg</span>
                            @else
                                <span class="text-sm text-gray-300">Sin peso</span>
                            @endif
                        </div>
                    </div>

                    {{-- Range indicator bar --}}
                    @if($w && ($piso || $techo))
                        @php
                            $low  = $piso  ?? ($techo - 10);
                            $high = $techo ?? ($piso  + 10);
                            $range = $high - $low;
                            $pct = $range > 0 ? max(0, min(100, (($w - $low) / $range) * 100)) : 50;
                        @endphp
                        <div class="mt-2">
                            <div class="relative h-1.5 bg-gray-100 rounded-full">
                                @if($piso && $techo)
                                    <div class="absolute inset-0 bg-green-100 rounded-full"></div>
                                @endif
                                <div class="absolute top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full border-2 border-white shadow
                                    {{ $w > ($techo ?? PHP_INT_MAX) ? 'bg-red-500' : ($w < ($piso ?? 0) ? 'bg-blue-500' : 'bg-teal-500') }}"
                                    style="left: calc({{ $pct }}% - 5px)">
                                </div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-300 mt-0.5">
                                <span>{{ $piso ?? '' }}</span>
                                <span>{{ $techo ?? '' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            @if($groups->isNotEmpty())
                {{-- Patient is in groups but hasn't attended yet --}}
                <div class="px-5 py-6 space-y-3">
                    <p class="text-sm text-gray-400 text-center mb-4">Sin asistencias registradas aún.</p>
                    @foreach($groups as $g)
                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-200 px-4 py-3">
                        <div class="w-2.5 h-2.5 rounded-full shrink-0
                            {{ $g->active ? 'bg-green-400' : 'bg-gray-300' }}"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">{{ $g->name }}</p>
                            @if($g->started_at)
                                <p class="text-xs text-gray-400 mt-0.5">Desde {{ $g->started_at->format('d/m/Y') }}</p>
                            @endif
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0
                            {{ $g->active ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                            {{ $g->active ? 'Activo' : 'Finalizado' }}
                        </span>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="px-5 py-10 text-center text-sm text-gray-400">Sin grupos ni sesiones registradas.</p>
            @endif
        @endforelse

        {{-- Groups enrolled in but never attended --}}
        @php
            $attendedGroupIds = $attendances->pluck('group_id')->unique();
            $unattenedGroups  = $groups->filter(fn($g) => !$attendedGroupIds->contains($g->id));
        @endphp
        @if($unattenedGroups->isNotEmpty() && $timelineWithChange->isNotEmpty())
            <div class="border-t border-gray-100 px-5 py-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Inscripto — sin asistencias</p>
                <div class="space-y-2">
                    @foreach($unattenedGroups as $g)
                    <div class="flex items-center gap-3 rounded-xl border border-dashed border-gray-200 px-4 py-3">
                        <div class="w-2.5 h-2.5 rounded-full shrink-0 {{ $g->active ? 'bg-yellow-400' : 'bg-gray-300' }}"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-700">{{ $g->name }}</p>
                            @if($g->started_at)
                                <p class="text-xs text-gray-400 mt-0.5">Desde {{ $g->started_at->format('d/m/Y') }}</p>
                            @endif
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0 bg-yellow-50 text-yellow-600">
                            Sin asistencias
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

</div>

@if($weightRecords->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const cd = @json($chartData);
    const datasets = [{
        label: 'Peso',
        data: cd.weights,
        borderColor: '#0d9488',
        backgroundColor: 'rgba(13,148,136,0.08)',
        borderWidth: 2.5,
        pointRadius: cd.weights.length > 20 ? 2 : 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.3,
    }];
    if (cd.piso)  datasets.push({ label: 'Piso ('  + cd.piso  + ' kg)', data: cd.labels.map(() => cd.piso),
        borderColor: '#16a34a', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });
    if (cd.techo) datasets.push({ label: 'Techo (' + cd.techo + ' kg)', data: cd.labels.map(() => cd.techo),
        borderColor: '#ef4444', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });

    new Chart(document.getElementById('weightChart').getContext('2d'), {
        type: 'line',
        data: { labels: cd.labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: !!(cd.piso || cd.techo), position: 'bottom',
                    labels: { font:{size:11}, boxWidth:20, padding:10 } },
                tooltip: { callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ' ' + ctx.parsed.y.toFixed(2) + ' kg'
                        : ctx.dataset.label
                }}
            },
            scales: {
                x: { ticks:{font:{size:11},color:'#9ca3af',maxRotation:45,autoSkip:true,maxTicksLimit:8}, grid:{display:false} },
                y: { ticks:{font:{size:11},color:'#9ca3af',callback:v=>v+' kg'}, grid:{color:'#f3f4f6'}, grace:'8%' }
            }
        }
    });
})();
</script>
@endif

<script>
async function loadAiAnalysis(force = false) {
    const btn  = document.getElementById('btn-ai');
    const text = document.getElementById('ai-text');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Analizando...';
    text.textContent = 'Generando análisis clínico...';
    text.className = 'text-sm text-gray-400 italic';
    try {
        const url = '{{ route("coordinator.patients.ai-analysis", $patient) }}' + (force ? '?force=1' : '');
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();
        text.textContent = data.analysis;
        text.className = 'text-sm text-gray-700 leading-relaxed';
    } catch {
        text.textContent = 'Error al conectar con el servicio de IA. Intentá nuevamente.';
        text.className = 'text-sm text-red-400 italic';
    }
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Regenerar';
}
</script>

@endsection
