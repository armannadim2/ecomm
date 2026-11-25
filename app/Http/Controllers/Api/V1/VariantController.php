<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Resources\Api\V1\VariantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * /api/v1/variants — what is sold: SKU, barcode, price, stock.
 * Stock is changed with /api/v1/stock (keeps a reason for every change), not here.
 */
class VariantController extends ApiController
{
    /** ?sku= or ?barcode= — the POS looks up a scanned barcode here. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'sku' => ['nullable', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'low_stock' => ['nullable', 'boolean'],
        ]);

        $query = ProductVariant::query()->with('product')->whereHas('product')->orderBy('id');
        $query->when($request->query('sku'), fn ($q, $sku) => $q->where('sku', $sku));
        $query->when($request->query('barcode'), fn ($q, $barcode) => $q->where('barcode', $barcode));
        $query->when($request->boolean('low_stock'), fn ($q) => $q->where('track_stock', true)->whereColumn('stock', '<=', 'low_stock_threshold'));
        $this->filterUpdatedSince($query, $request);

        return VariantResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(ProductVariant $variant): VariantResource
    {
        return new VariantResource($variant->load('product'));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(self::rules(prefix: '', withStock: true));

        $variant = $product->variants()->create(self::attributes($data) + [
            'position' => ($product->variants()->max('position') ?? -1) + 1,
        ]);

        return (new VariantResource($variant->load('product')))->response()->setStatusCode(201);
    }

    public function update(Request $request, ProductVariant $variant): VariantResource
    {
        if ($request->has('stock')) {
            throw new DomainRuleException('El stock se cambia con POST /api/v1/stock/adjust o /api/v1/stock/set.');
        }

        $data = $request->validate(self::rules(prefix: '', withStock: false, ignore: $variant, partial: true));
        $variant->update(self::attributes($data));

        return new VariantResource($variant->fresh('product'));
    }

    public function destroy(ProductVariant $variant): Response
    {
        if ($variant->product->variants()->count() <= 1) {
            throw new DomainRuleException('Un producto necesita al menos una variante. Borra el producto en su lugar.');
        }

        $variant->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    public static function rules(string $prefix, bool $withStock, ?ProductVariant $ignore = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return array_filter([
            $prefix.'name' => ['nullable', 'string', 'max:120'],
            $prefix.'sku' => [$required, 'string', 'max:64', 'distinct', Rule::unique('product_variants', 'sku')->ignore($ignore)],
            $prefix.'barcode' => ['nullable', 'string', 'max:64'],
            $prefix.'price_cents' => [$required, 'integer', 'min:1', 'max:100000000'],
            $prefix.'compare_at_price_cents' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            $prefix.'stock' => $withStock ? ['nullable', 'integer', 'min:0', 'max:1000000'] : null,
            $prefix.'track_stock' => ['sometimes', 'boolean'],
            $prefix.'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            $prefix.'weight_grams' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            $prefix.'position' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public static function attributes(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'name', 'sku', 'barcode', 'price_cents', 'compare_at_price_cents', 'stock',
            'track_stock', 'low_stock_threshold', 'weight_grams', 'position',
        ]));
    }
}
