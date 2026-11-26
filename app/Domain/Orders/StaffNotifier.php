<?php

namespace App\Domain\Orders;

use App\Domain\Orders\Models\Order;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

/** Bell notifications in the admin for the staff who handle orders. */
class StaffNotifier
{
    public function newOrder(Order $order): void
    {
        $this->send(
            $order,
            'Nuevo pedido '.$order->number,
            $order->fullName().' · '.Money::format($order->total_cents).' · '.$order->fulfillment->getLabel(),
            'heroicon-o-shopping-bag',
        );
    }

    /** Something needs a person: e.g. money arrived for an order that had already been cancelled. */
    public function attention(Order $order, string $title, string $body): void
    {
        $this->send($order, $title, $body, 'heroicon-o-exclamation-triangle', danger: true);
    }

    /** @return Collection<int, Staff> Active owners and staff with the permission. */
    public function staffWith(string $permissionName): Collection
    {
        $permission = Permission::query()->where('name', $permissionName)->where('guard_name', Staff::GUARD)->first();

        return Staff::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->role(Staff::OWNER_ROLE)
                ->when($permission, fn ($q) => $q
                    ->orWhereHas('roles.permissions', fn ($p) => $p->whereKey($permission->id))
                    ->orWhereHas('permissions', fn ($p) => $p->whereKey($permission->id))))
            ->get();
    }

    private function send(Order $order, string $title, string $body, string $icon, bool $danger = false): void
    {
        $staff = $this->staffWith('orders.view');

        if ($staff->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->actions([
                Action::make('open')->label('Ver pedido')->button()
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin')),
            ]);

        if ($danger) {
            $notification->danger();
        }

        $notification->sendToDatabase($staff);
    }
}
