<?php

namespace App\Domain\WhatsApp\Policies;

use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/** WhatsApp inbox: reading needs `whatsapp.view`, answering and taking over need `whatsapp.reply`. */
class WhatsAppPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->can('whatsapp.view');
    }

    public function view(Staff $staff, Model $model): bool
    {
        return $staff->can('whatsapp.view');
    }

    public function reply(Staff $staff, Model $model): bool
    {
        return $staff->can('whatsapp.reply');
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function update(Staff $staff, Model $model): bool
    {
        return $staff->can('whatsapp.reply');
    }

    public function delete(Staff $staff, Model $model): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
