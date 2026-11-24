<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Spatie\Permission\Models\Role;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo rol')
                ->mutateDataUsing(fn (array $data) => [...$data, 'guard_name' => Staff::GUARD])
                ->after(fn (Role $record) => RoleResource::afterPermissionsChanged($record)),
        ];
    }
}
