<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Actions\UnpublishProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** /api/v1/products — catalog sync for ERPs, marketplaces and the shop POS. */
class ProductController extends ApiController
{
    private const RELATIONS = ['brand', 'categories', 'variants', 'media'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'category' => ['nullable', 'string'],
            'brand' => ['nullable', 'integer'],
            'sku' => ['nullable', 'string', 'max:64'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Product::query()->with(self::RELATIONS)->orderBy('id');

        $query->when($request->query('status'), fn ($q, $status) => $q->where('status', $status));
        $query->when($request->query('brand'), fn ($q, $brand) => $q->where('brand_id', $brand));
        $query->when($request->query('category'), fn ($q, $ulid) => $q->whereHas('categories', fn ($c) => $c->where('ulid', $ulid)));
        $query->when($request->query('sku'), fn ($q, $sku) => $q->whereHas('variants', fn ($v) => $v->where('sku', $sku)));
        $query->when($request->query('search'), fn ($q, $term) => $q->where('name', 'like', '%'.addcslashes($term, '%_\\').'%'));
        $this->filterUpdatedSince($query, $request);

        return ProductResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(self::RELATIONS));
    }

    public function store(Request $request, PublishProduct $publish): JsonResponse
    {
        $data = $request->validate([
            ...$this->productRules(creating: true),
            'status' => ['nullable', Rule::in([ProductStatus::Draft->value, ProductStatus::Published->value])],
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            ...VariantController::rules(prefix: 'variants.*.', withStock: true),
        ]);

        $product = DB::transaction(function () use ($data, $publish): Product {
            $product = Product::query()->create($this->productAttributes($data));
            $this->syncCategories($product, $data);

            foreach (array_values($data['variants']) as $position => $variant) {
                $product->variants()->create(VariantController::attributes($variant) + ['position' => $position]);
            }

            if (($data['status'] ?? null) === ProductStatus::Published->value) {
                $publish->handle($product);
            }

            return $product;
        });

        return (new ProductResource($product->fresh(self::RELATIONS)))->response()->setStatusCode(201);
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $data = $request->validate($this->productRules(creating: false));

        DB::transaction(function () use ($product, $data) {
            $product->update($this->productAttributes($data));
            $this->syncCategories($product, $data);
        });

        return new ProductResource($product->fresh(self::RELATIONS));
    }

    /** Moves the product to the admin trash (it can be restored there). */
    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }

    public function publish(Product $product, PublishProduct $publish): ProductResource
    {
        return new ProductResource($publish->handle($product)->fresh(self::RELATIONS));
    }

    /** ?archive=1 archives it instead of returning it to draft. */
    public function unpublish(Request $request, Product $product, UnpublishProduct $unpublish): ProductResource
    {
        return new ProductResource($unpublish->handle($product, $request->boolean('archive'))->fresh(self::RELATIONS));
    }

    /** @return array<string, mixed> */
    private function productRules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:200'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'tax_class' => ['sometimes', Rule::enum(TaxClass::class)],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'category_ids' => [$creating ? 'nullable' : 'sometimes', 'array', 'max:20'],
            'category_ids.*' => ['string', Rule::exists('categories', 'ulid')->whereNull('deleted_at')],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function productAttributes(array $data): array
    {
        // The description may contain HTML; the shop sanitises it when showing it (Str::sanitizeHtml).
        return array_intersect_key($data, array_flip([
            'name', 'short_description', 'description', 'tax_class', 'brand_id', 'seo_title', 'seo_description',
        ]));
    }

    /** @param  array<string, mixed>  $data */
    private function syncCategories(Product $product, array $data): void
    {
        if (array_key_exists('category_ids', $data)) {
            $product->categories()->sync(Category::query()->whereIn('ulid', $data['category_ids'] ?? [])->pluck('id'));
        }
    }
}
