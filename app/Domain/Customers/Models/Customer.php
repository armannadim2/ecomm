<?php

namespace App\Domain\Customers\Models;

use App\Domain\Customers\Policies\CustomerPolicy;
use App\Domain\Orders\Models\Order;
use Database\Factories\CustomerFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A shopper with an account. Guests can also buy without one (Phase 2 checkout).
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(CustomerFactory::class)]
#[UsePolicy(CustomerPolicy::class)]
class Customer extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<CustomerFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<Address, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest('id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function hasMarketingConsent(): bool
    {
        return $this->marketing_consent_at !== null;
    }
}
