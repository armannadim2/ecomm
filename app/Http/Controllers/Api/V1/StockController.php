<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Resources\Api\V1\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * /api/v1/stock — stock changes from the POS, warehouse or supplier, in batches.
 * All lines are applied together or none (a till sale with 3 items never half-applies).
 * Every change is logged with its reason, like changes made in the admin.
 */
class StockController extends ApiController
{
    /** Add or remove units: {"items":[{"sku":"TM-8421","change":-1,"reason":"Venta TPV #123"}]} */
    public function adjust(Request $request, AdjustStock $adjust): AnonymousResourceCollection
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.sku' => ['required_without:items.*.variant_id', 'nullable', 'string', 'max:64'],
            'items.*.variant_id' => ['required_without:items.*.sku', 'nullable', 'string'],
            'items.*.change' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'items.*.reason' => ['nullable', 'string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        return VariantResource::collection($this->apply($data, function (ProductVariant $variant, array $item) {
            return (int) $item['change'];
        }, $adjust));
    }

    /** Set the counted quantity: {"items":[{"sku":"TM-8421","quantity":12}],"reason":"Inventario"} */
    public function set(Request $request, AdjustStock $adjust): AnonymousResourceCollection
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.sku' => ['required_without:items.*.variant_id', 'nullable', 'string', 'max:64'],
            'items.*.variant_id' => ['required_without:items.*.sku', 'nullable', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'items.*.reason' => ['nullable', 'string', 'max:200'],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        return VariantResource::collection($this->apply($data, function (ProductVariant $variant, array $item) {
            return (int) $item['quantity'] - $variant->stock;
        }, $adjust));
    }

    /**
     * @param  array{items: list<array<string, mixed>>, reason?: string|null}  $data
     * @param  callable(ProductVariant, array<string, mixed>): int  $change
     * @return list<ProductVariant>
     */
    private function apply(array $data, callable $change, AdjustStock $adjust): array
    {
        $client = request()->user();

        return DB::transaction(function () use ($data, $change, $adjust, $client): array {
            $result = [];

            foreach ($data['items'] as $i => $item) {
                $variant = ProductVariant::query()
                    ->when($item['variant_id'] ?? null, fn ($q, $ulid) => $q->where('ulid', $ulid), fn ($q) => $q->where('sku', $item['sku']))
                    ->lockForUpdate()
                    ->first();

                if ($variant === null) {
                    throw new DomainRuleException('Línea '.($i + 1).': no existe ninguna variante con '.(isset($item['variant_id']) ? 'id '.$item['variant_id'] : 'SKU '.$item['sku']).'.');
                }

                $delta = $change($variant, $item);
                $reason = trim((string) ($item['reason'] ?? $data['reason'] ?? '')) ?: 'API';

                if ($delta !== 0) {
                    try {
                        $variant = $adjust->handle($variant, $delta, $reason.' ('.$client->name.')');
                    } catch (DomainRuleException $e) {
                        throw new DomainRuleException('Línea '.($i + 1).' ('.$variant->sku.'): '.$e->getMessage(), previous: $e);
                    }
                }

                $result[] = $variant->load('product');
            }

            return $result;
        });
    }
}
