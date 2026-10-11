<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->refuseLocalAuthOutsideLaptop();
    }

    // Local login trusts plain headers, so anyone could act as an admin with it.
    // We stop the app at boot if somebody turns it on in the cloud by mistake.
    private function refuseLocalAuthOutsideLaptop(): void
    {
        $isLocalAuth = config('wonderland.auth_mode') === 'local';

        if ($isLocalAuth && ! $this->app->environment(['local', 'testing'])) {
            throw new RuntimeException('AUTH_MODE=local is allowed only when APP_ENV is local. Use AUTH_MODE=cognito.');
        }
    }
}
