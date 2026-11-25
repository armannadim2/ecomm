<?php

namespace App\Domain\Integrations\Models;

use App\Domain\Integrations\Policies\IntegrationPolicy;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An outside system allowed to use the REST API (shop POS, ERP, marketplace…).
 * Its key is a Sanctum token whose abilities are the ApiScopes chosen by the owner.
 * Revoked clients keep their history but every request is refused.
 */
#[UsePolicy(IntegrationPolicy::class)]
#[Fillable(['name', 'description', 'created_by'])]
class ApiClient extends Model
{
    use HasApiTokens, HasUlids, LogsActivity;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    /**
     * Replaces any previous key. The plain-text key is returned once and never stored.
     *
     * @param  list<string>  $scopes
     */
    public function issueKey(array $scopes): NewAccessToken
    {
        $this->tokens()->delete();

        return $this->createToken('api', array_values($scopes));
    }

    /** @return list<string> */
    public function scopes(): array
    {
        return $this->tokens()->latest('id')->first()?->abilities ?? [];
    }

    public function revoke(): void
    {
        $this->tokens()->delete();
        $this->forceFill(['revoked_at' => now()])->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('integrations')->logOnly(['name', 'revoked_at'])->logOnlyDirty();
    }
}
