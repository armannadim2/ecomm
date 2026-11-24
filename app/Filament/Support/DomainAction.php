<?php

namespace App\Filament\Support;

use App\Domain\Shared\Exceptions\DomainRuleException;
use Closure;
use Filament\Notifications\Notification;

/**
 * Runs a domain Action from the admin panel and turns the result into a
 * friendly notification. Broken business rules show their own message.
 */
final class DomainAction
{
    public static function run(Closure $callback, string $success, string $failureTitle = 'No se ha podido completar'): bool
    {
        try {
            $callback();
        } catch (DomainRuleException $e) {
            Notification::make()->danger()->title($failureTitle)->body($e->getMessage())->persistent()->send();

            return false;
        }

        Notification::make()->success()->title($success)->send();

        return true;
    }
}
