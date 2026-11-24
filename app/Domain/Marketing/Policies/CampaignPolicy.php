<?php

namespace App\Domain\Marketing\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;

class CampaignPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'campaigns';
    }
}
