<?php

use App\Domain\Catalog\Actions\SchedulePublication;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Products\Pages\EditProduct;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;

function readyDraft(): Product
{
    $product = Product::factory()->sellable(priceCents: 1000, stock: 3)->create();
    $product->categories()->attach(Category::factory()->create());

    return $product->fresh();
}

it('publishes a scheduled product when its time comes, and not before', function () {
    $product = readyDraft();
    app(SchedulePublication::class)->handle($product, now()->addHours(2));

    artisan('catalog:publish-scheduled')->assertSuccessful();
    expect($product->fresh()->status)->toBe(ProductStatus::Draft);

    $this->travel(2)->hours();
    $this->travel(1)->minutes();
    artisan('catalog:publish-scheduled')->assertSuccessful();

    expect($product->fresh())
        ->status->toBe(ProductStatus::Published)
        ->scheduled_publish_at->toBeNull();
});

it('checks the product is ready to sell when scheduling, and refuses past dates', function () {
    $notReady = Product::factory()->create();

    expect(fn () => app(SchedulePublication::class)->handle($notReady, now()->addDay()))
        ->toThrow(DomainRuleException::class);
    expect(fn () => app(SchedulePublication::class)->handle(readyDraft(), now()->subHour()))
        ->toThrow(DomainRuleException::class, 'Elige una fecha y hora en el futuro');
});

it('drops the schedule and tells the uploader when the product stopped being ready', function () {
    $owner = staffWithRole(Staff::OWNER_ROLE);
    $product = readyDraft();
    $product->forceFill(['created_by_staff_id' => $owner->id])->save();
    app(SchedulePublication::class)->handle($product, now()->addHour());
    $product->categories()->detach();

    $this->travel(2)->hours();
    artisan('catalog:publish-scheduled')->assertSuccessful();

    expect($product->fresh())->status->toBe(ProductStatus::Draft)->scheduled_publish_at->toBeNull()
        ->and($owner->notifications()->count())->toBe(1);
});

it('lets staff schedule and cancel from the product page', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $product = readyDraft();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('schedulePublication', ['at' => now()->addDays(3)->format('Y-m-d H:i:s')])
        ->assertNotified('Publicación programada');
    expect($product->fresh()->scheduled_publish_at)->not->toBeNull();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('cancelSchedule')
        ->assertNotified('Programación cancelada');
    expect($product->fresh()->scheduled_publish_at)->toBeNull();
});
