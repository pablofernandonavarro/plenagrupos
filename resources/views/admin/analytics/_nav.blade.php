@php
    $current = $active ?? 'index';
@endphp
<div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
    <a href="{{ route('admin.analytics.index') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $current === 'index' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Resumen</a>
    <a href="{{ route('admin.analytics.groups') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $current === 'groups' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Por grupo</a>
    <a href="{{ route('admin.analytics.inbody') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $current === 'inbody' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">InBody</a>
    <a href="{{ route('admin.analytics.cohorts') }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $current === 'cohorts' ? 'bg-teal-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">Cohortes</a>
</div>
