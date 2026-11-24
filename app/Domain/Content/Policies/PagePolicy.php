<?php

namespace App\Domain\Content\Policies;

use App\Domain\Content\Models\Page;
use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

class PagePolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'pages';
    }

    /** The home page ("inicio") holds the home blocks and cannot be deleted. */
    public function delete(Staff $staff, Model $model): bool
    {
        return ! ($model instanceof Page && $model->isHome()) && parent::delete($staff, $model);
    }

    public function forceDelete(Staff $staff, Model $model): bool
    {
        return ! ($model instanceof Page && $model->isHome()) && parent::forceDelete($staff, $model);
    }
}
