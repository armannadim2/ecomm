<?php

namespace App\Filament\Support;

use App\Domain\Staff\Models\Staff;
use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

/**
 * "Confirm your password" for sensitive admin actions (staff, roles, API keys, webhooks): add
 * ConfirmPassword::field() to the action's form. After a correct password the field is not asked
 * again for 10 minutes (like GitHub's "sudo mode"). 2FA already protects the login; this protects
 * an unattended, logged-in computer.
 */
final class ConfirmPassword
{
    public const SESSION_KEY = 'staff.password_confirmed_at';

    private const GRACE_SECONDS = 600;

    public static function field(): TextInput
    {
        return TextInput::make('current_password')
            ->label('Tu contraseña')
            ->helperText('Por seguridad, confirma tu contraseña para hacer este cambio. No te la volveremos a pedir en 10 minutos.')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->dehydrated(false)
            ->visible(fn () => ! self::recentlyConfirmed())
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                $staff = auth(Staff::GUARD)->user();

                if (! $staff || ! is_string($value) || ! Hash::check($value, $staff->password)) {
                    $fail('La contraseña no es correcta.');

                    return;
                }

                session()->put(self::SESSION_KEY, now()->getTimestamp());
            });
    }

    public static function recentlyConfirmed(): bool
    {
        return now()->getTimestamp() - (int) session(self::SESSION_KEY, 0) < self::GRACE_SECONDS;
    }
}
