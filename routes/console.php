<?php

use App\Domain\Payments\Actions\ReconcilePayments;
use App\Domain\Staff\Models\Staff;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Scheduler. Hostinger shared hosting has no daemons: one cron entry runs it every minute
 *   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 * and the queue (e-mails, webhooks, image conversions) is worked from here.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();

// Online payments: check PayPal payments left half-way and cancel unpaid orders whose reservation ended.
Artisan::command('payments:reconcile', function (ReconcilePayments $reconcile) {
    $result = $reconcile->handle();
    $this->info("Captured: {$result['captured']} · Expired orders: {$result['expired']}");
})->purpose('Reconcile pending online payments and release stock of unpaid orders');
Schedule::command('payments:reconcile')->everyFiveMinutes()->withoutOverlapping(10);

// Products scheduled to go live (CAT-12).
Schedule::command('catalog:publish-scheduled')->everyMinute()->withoutOverlapping();

// Backups (OPS-04): database + storage/app, kept 7 days daily / 16 days / 8 weeks… (config/backup.php).
// The monitor e-mails BACKUP_NOTIFY_EMAIL if the newest backup is older than a day.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00');

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('activitylog:clean')->weekly();
// WhatsApp conversations: kept 2 years (privacy policy).
Schedule::command('whatsapp:prune')->dailyAt('04:00');

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
