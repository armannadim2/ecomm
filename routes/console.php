<?php

use App\Domain\Staff\Models\Staff;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Local development only: current 2FA code of a staff member, to log in to the admin
// with the demo accounts. Refuses to run in production.
Artisan::command('staff:2fa-code {email=owner@ecomm.test}', function (string $email) {
    if (app()->isProduction()) {
        $this->error('Not available in production.');

        return 1;
    }

    $staff = Staff::query()->where('email', $email)->first();

    if (! $staff) {
        $this->error("No staff member with e-mail {$email}.");

        return 1;
    }

    if (! $staff->getAppAuthenticationSecret()) {
        $this->warn("{$email} has not set up 2FA yet: log in and scan the QR code with an authenticator app.");

        return 1;
    }

    $this->info(AppAuthentication::make()->getCurrentCode($staff));
    $this->line('Valid for about 30 seconds.');

    return 0;
})->purpose('Show the current admin 2FA code of a staff member (local development only)');
