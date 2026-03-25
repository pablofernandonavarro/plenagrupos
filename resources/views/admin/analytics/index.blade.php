@extends('layouts.app')
@section('title', 'Indicadores')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Indicadores</h1>
        <p class="text-gray-500 text-sm mt-1">Métricas derivadas de asistencias, pesos e InBody (sin nuevos formularios).</p>
    </div>

    @include('admin.analytics._nav', ['active' => 'index'])

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.adherence.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:border-amber-300 transition group">
            <h2 class="font-semibold text-gray-800 group-hover:text-amber-700">Adherencia y export CSV</h2>
            <p class="text-sm text-gray-500 mt-2">Última visita y peso por paciente, alertas por días sin registro y descarga de datos para Excel.</p>
        </a>
        <a href="{{ route('admin.analytics.groups') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:border-teal-300 transition group">
            <h2 class="font-semibold text-gray-800 group-hover:text-teal-700">Comparativa por grupo</h2>
            <p class="text-sm text-gray-500 mt-2">Asistencias por semana, % en rango de mantenimiento y pérdida media de peso por grupo.</p>
        </a>
        <a href="{{ route('admin.analytics.inbody') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:border-teal-300 transition group">
            <h2 class="font-semibold text-gray-800 group-hover:text-teal-700">InBody</h2>
            <p class="text-sm text-gray-500 mt-2">Promedios mensuales (grasa visceral, % grasa, masa muscular) y evolución por paciente.</p>
        </a>
        <a href="{{ route('admin.analytics.cohorts') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 hover:border-teal-300 transition group">
            <h2 class="font-semibold text-gray-800 group-hover:text-teal-700">Cohortes y retención</h2>
            <p class="text-sm text-gray-500 mt-2">Visitas en los primeros 30/60 días desde el alta y curva semanal (semanas 1–8).</p>
        </a>
    </div>
</div>
@endsection
