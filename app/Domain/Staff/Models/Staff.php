<?php

namespace App\Domain\Staff\Models;

use App\Domain\Staff\Policies\StaffPolicy;
use Database\Factories\StaffFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Shop owner and employees who use the admin panel.
 * Customers are a separate model and never share this table or guard.
 */
#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(StaffFactory::class)]
#[UsePolicy(StaffPolicy::class)]
class Staff extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory, HasRoles, InteractsWithAppAuthentication,
        InteractsWithAppAuthenticationRecovery, LogsActivity, Notifiable, SoftDeletes;

    public const GUARD = 'staff';

    /** Role that always has every permission. Cannot be renamed or deleted. */
    public const OWNER_ROLE = 'Propietario';

    protected $table = 'staff';

    protected string $guard_name = self::GUARD;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Checked on every request, so deactivating someone logs them out at once.
        return $this->is_active && ! $this->trashed();
    }

    public function isOwner(): bool
    {
        return $this->hasRole(self::OWNER_ROLE);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('staff')
            ->logOnly(['name', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
