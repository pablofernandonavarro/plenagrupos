<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

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
    }
}
