<?php

namespace App\Domain\Customers\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'first_name', 'last_name', 'company', 'tax_id', 'line1', 'line2',
    'postal_code', 'city', 'province', 'phone', 'is_default',
])]
#[UseFactory(AddressFactory::class)]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, HasUlids;

    /** Spain only at launch (Peninsula + Balearic Islands). */
    protected $attributes = ['country' => 'ES'];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return list<string> */
    public function lines(): array
    {
        return array_values(array_filter([
            trim("{$this->first_name} {$this->last_name}"),
            $this->company,
            $this->line1,
            $this->line2,
            "{$this->postal_code} {$this->city} ({$this->province})",
            $this->phone,
        ]));
    }
}
