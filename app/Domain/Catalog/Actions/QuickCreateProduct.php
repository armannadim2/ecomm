<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * "Subir producto" in one step, like Vinted or Wallapop: photos, title,
 * price, category, done. Creates the product with a single variant and an
 * automatic SKU. Staff who can publish go live at once; everyone else's
 * upload is sent for review.
 */
class QuickCreateProduct
{
    public function __construct(
        private readonly PublishProduct $publish,
        private readonly SubmitProductForReview $submit,
    ) {}

    /**
     * @param  array{name: string, price_cents: int, category_id: int, stock?: int, short_description?: ?string,
     *     brand_id?: ?int, photos: list<string>}  $data  photos = paths on the "local" disk, in display order
     */
    public function handle(array $data, Staff $uploader): Product
    {
        $product = DB::transaction(function () use ($data, $uploader): Product {
            $product = new Product([
                'name' => $data['name'],
                'short_description' => $data['short_description'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'tax_class' => TaxClass::General,
            ]);
            $product->created_by_staff_id = $uploader->id;
            $product->save();

            $product->categories()->sync([$data['category_id']]);

            $product->variants()->create([
                'sku' => $this->newSku(),
                'price_cents' => $data['price_cents'],
                'stock' => $data['stock'] ?? 1,
            ]);

            return $product;
        });

        foreach ($data['photos'] as $path) {
            $product->addMediaFromDisk($path, 'local')->toMediaCollection(Product::IMAGES);
            Storage::disk('local')->delete($path);
        }

        $product->setRelation('variants', $product->variants()->get());

        return $uploader->can('publish', $product)
            ? $this->publish->handle($product, $uploader)
            : $this->submit->handle($product);
    }

    private function newSku(): string
    {
        do {
            $sku = 'P-'.Str::upper(Str::random(8));
        } while (ProductVariant::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
