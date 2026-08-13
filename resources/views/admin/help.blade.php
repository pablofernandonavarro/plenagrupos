@extends('layouts.app')
@section('title', 'Ayuda')

@section('content')
<div class="space-y-6 max-w-5xl">

    {{-- Header --}}
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al dashboard
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Ayuda</h1>
        <p class="text-gray-500 text-sm mt-0.5">Guía en criollo de cómo funciona todo, sin vueltas técnicas.</p>
    </div>

    {{-- Grupos --}}
    <x-help-card title="Grupos" icon="info" color="blue">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">🟦 Grupos manuales</p>
                <p class="text-xs opacity-80">Vos o el coordinador los abren y cierran a mano con un botón. Útiles para encuentros puntuales que no siguen un horario fijo.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🔄 Grupos recurrentes</p>
                <p class="text-xs opacity-80">Se abren y cierran solos según el horario que configuraste — no hace falta que nadie los toque todos los días. Al crearlos, elegís cada cuánto se repiten:</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <strong>Todos los días</strong></li>
                    <li>• <strong>Todas las semanas</strong>, en los días que marques (ej: lunes y jueves)</li>
                    <li>• <strong>Todos los meses</strong>, de dos formas posibles:
                        <ul class="ml-4 mt-1 space-y-1">
                            <li>– El mismo número de día cada mes (ej: siempre el 14)</li>
                            <li>– Un día de la semana en particular, ej: <strong>"2do miércoles de cada mes"</strong> — esto se recalcula bien cada mes, no se va desviando con el tiempo</li>
                        </ul>
                    </li>
                    <li>• <strong>Todos los años</strong></li>
                </ul>
            </div>
            <div>
                <p class="font-medium mb-1">📍 Modalidad</p>
                <p class="text-xs opacity-80">Presencial (solo QR), Virtual (link + QR) o Híbrido (ambas).</p>
            </div>
        </div>
    </x-help-card>

    {{-- QR y asistencia --}}
    <x-help-card title="Código QR y asistencia" icon="check" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📷 Cómo se registran los pacientes</p>
                <p class="text-xs opacity-80">Cada grupo tiene su propio QR, disponible en la pantalla del grupo. El paciente lo escanea al llegar y queda registrada su entrada; cuando se va, se marca la salida (a mano, o automáticamente cuando termina el horario del grupo).</p>
            </div>
            <div>
                <p class="font-medium mb-1">🟢 "En vivo"</p>
                <p class="text-xs opacity-80">Ese cartel significa que el grupo está teniendo su sesión en este momento exacto — no que "está activo en general". Un grupo puede estar vigente pero no en vivo si todavía no llegó el horario de hoy.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🏷️ "Pertenece" vs "Visita"</p>
                <p class="text-xs opacity-80">Un paciente puede escanear el QR de un grupo que no es el suyo (por ejemplo, si un día va a otro horario). En ese caso queda marcado como "Visita" en vez de "Pertenece", y el grupo que aparece como "Pacientes (N)" en los listados solo cuenta a los que realmente pertenecen ahí.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Coordinadores --}}
    <x-help-card title="Coordinadores" icon="info" color="purple">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">👥 Qué pueden hacer</p>
                <p class="text-xs opacity-80">Cada coordinador ve la misma pantalla de detalle de grupo que vos como admin: código QR, agregar/quitar coordinadores y pacientes, ver quién está presente, historial de sesiones anteriores. La diferencia es que no pueden finalizar un programa recurrente para siempre — solo pueden abrir o cerrar la sesión del día.</p>
            </div>
            <div>
                <p class="font-medium mb-1">⭐ Convertir un coordinador en administrador</p>
                <p class="text-xs opacity-80">En la ficha de edición de un coordinador (Usuarios → Editar) hay un botón "Hacer administrador" al final, en la sección "Zona de riesgo". Le da acceso total a la app, así que usalo solo si de verdad querés darle ese nivel de acceso. A los pacientes no se les puede dar ese permiso.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Planes de pacientes --}}
    <x-help-card title="Planes de los pacientes" icon="info" color="teal">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📋 Los tres planes</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <strong>Descenso:</strong> el paciente busca bajar de peso</li>
                    <li>• <strong>Mantenimiento:</strong> ya llegó a su peso y lo sostiene dentro de un rango</li>
                    <li>• <strong>Mantenimiento Pleno:</strong> variante de mantenimiento, asiste a los mismos grupos de mantenimiento</li>
                </ul>
            </div>
            <div>
                <p class="font-medium mb-1">⚠️ Por qué importa asignar un plan</p>
                <p class="text-xs opacity-80">El plan define cuántas visitas mensuales puede hacer el paciente a cada tipo de grupo (lo configurás en "Reglas"). Un paciente <strong>sin plan asignado</strong> — típicamente alguien que se registró solo, sin que nadie lo cargara — queda sin ningún límite aplicado. En la lista de Usuarios, esos pacientes aparecen con un cartel rojo "Sin plan · asignar" que te lleva directo a arreglarlo.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🏠 Grupo de pertenencia</p>
                <p class="text-xs opacity-80">Es el grupo "principal" del paciente. Tanto vos como el paciente (desde su propio perfil) solo pueden elegir grupos que coincidan con el tipo de su plan — un paciente de Descenso no va a ver grupos de Mantenimiento en ese menú, y viceversa.</p>
            </div>
        </div>
    </x-help-card>

    {{-- WhatsApp --}}
    <x-help-card title="WhatsApp" icon="check" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">🔗 Vincular el número</p>
                <p class="text-xs opacity-80">En el menú "WhatsApp" del panel de admin, tocá "Vincular WhatsApp" y escaneá el código QR con el WhatsApp del celular que va a usar la app para mandar mensajes (igual que WhatsApp Web). Una vez vinculado, se queda conectado — no hace falta repetirlo cada vez.</p>
            </div>
            <div>
                <p class="font-medium mb-1">✉️ Mandar un mensaje a un paciente o coordinador</p>
                <p class="text-xs opacity-80">En la lista de Usuarios, cualquier persona con teléfono cargado tiene un botón "Enviar WhatsApp". Te pregunta el número (por si querés corregirlo) y el mensaje, y lo manda directo.</p>
            </div>
            <div>
                <p class="font-medium mb-1">📞 Formato correcto del teléfono</p>
                <p class="text-xs opacity-80">Tiene que llevar el código de país y de área, sin el "15" del discado local. Ejemplo correcto: <strong>5491122334455</strong>. Si el número está mal escrito, el sistema te avisa antes de guardar.</p>
            </div>
            <div class="pt-2 border-t border-current opacity-30">
                <p class="text-xs">⚠️ <strong>Cuidado con mandar muchos mensajes seguidos</strong> a números que nunca hablaron con vos — no es la app oficial de WhatsApp Business, así que mandar en cantidad y de golpe puede hacer que WhatsApp banee el número. Para recordatorios puntuales a pacientes conocidos no hay problema.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Adherencia --}}
    <x-help-card title="Adherencia (seguimiento de pacientes)" icon="info" color="purple">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📊 Qué muestra esta pantalla</p>
                <p class="text-xs opacity-80">Por cada paciente: cuándo fue su última visita a un grupo, cuándo se pesó por última vez, y cuándo se hizo su último InBody. Sirve para detectar quién se está por perder de vista.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🎚️ Umbrales de alerta</p>
                <p class="text-xs opacity-80">Vos elegís cuántos días sin actividad son "demasiados" para cada cosa (por defecto: 14 días sin visitar, 14 sin pesarse, 30 sin InBody). Si un paciente supera ese número, aparece marcado.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🔴 "Atrasado" vs "Sin registro"</p>
                <p class="text-xs opacity-80">Si dice <strong>"atrasado"</strong> es porque el paciente sí tiene un registro, pero hace tiempo que no lo actualiza. Si dice directamente <strong>"Sin InBody"</strong> (o peso/visita) es porque nunca tuvo ninguno.</p>
            </div>
            <div>
                <p class="font-medium mb-1">🔍 Buscar un paciente puntual</p>
                <p class="text-xs opacity-80">Arriba de la tabla hay un buscador por nombre o email, para no tener que scrollear toda la lista.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Exportar datos --}}
    <x-help-card title="Exportar datos" icon="check" color="blue">
        <div class="space-y-2">
            <p class="text-xs opacity-80">Desde "Adherencia" podés descargar en Excel (CSV): todas las asistencias, todos los pesos registrados, todos los InBody, y un listado de qué paciente pertenece a qué grupo (con el canal por el que se sumó: QR o alta manual). Los archivos se generan al momento, con la fecha en el nombre.</p>
        </div>
    </x-help-card>

</div>
@endsection
