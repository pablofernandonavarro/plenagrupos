<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\User;
use App\Models\WeightRecord;
use App\Observers\AppointmentObserver;
use App\Observers\GroupAttendanceObserver;
use App\Observers\InbodyRecordObserver;
use App\Observers\UserObserver;
use App\Observers\WeightRecordObserver;
use App\Services\AiCompletionProvider;
use App\Services\EmbeddingProvider;
use App\Services\GroqCompletionProvider;
use App\Services\OpenAiEmbeddingProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings interface->implementación de IA: permiten cambiar de proveedor (hoy Groq
        // para completions, OpenAI para embeddings) sin tocar quien los consume.
        $this->app->bind(AiCompletionProvider::class, GroqCompletionProvider::class);
        $this->app->bind(EmbeddingProvider::class, OpenAiEmbeddingProvider::class);
    }

    public function boot(): void
    {
        Pulse::user(function (User $user) {
            return [
                'name'   => $user->name,
                'extra'  => $user->email,
                'avatar' => $user->avatar
                    ? secure_asset('storage/' . $user->avatar)
                    : null,
            ];
        });

        // Mantienen sincronizados los embeddings de las notas de texto libre del paciente
        // (peso, InBody, coordinador, turnos, objetivo/estado) con lo que ya está guardado.
        WeightRecord::observe(WeightRecordObserver::class);
        InbodyRecord::observe(InbodyRecordObserver::class);
        GroupAttendance::observe(GroupAttendanceObserver::class);
        Appointment::observe(AppointmentObserver::class);
        User::observe(UserObserver::class);
    }
}
