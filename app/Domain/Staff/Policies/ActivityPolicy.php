<?php

namespace App\Domain\Staff\Policies;

use App\Domain\Staff\Models\Staff;
use Spatie\Activitylog\Models\Activity;

/** The activity log is read-only for everyone. */
class ActivityPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->can('activity.view');
    }

    public function view(Staff $staff, Activity $activity): bool
    {
        return $staff->can('activity.view');
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function update(Staff $staff, Activity $activity): bool
    {
        return false;
    }

    public function delete(Staff $staff, Activity $activity): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
