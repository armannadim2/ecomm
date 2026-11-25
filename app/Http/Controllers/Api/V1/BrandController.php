<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Brand;
use App\Http\Resources\Api\V1\BrandResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** /api/v1/brands */
class BrandController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Brand::query()->orderBy('name');
        $this->filterUpdatedSince($query, $request);

        return BrandResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        return (new BrandResource(Brand::query()->create($data)))->response()->setStatusCode(201);
    }

    public function update(Request $request, Brand $brand): BrandResource
    {
        $brand->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'is_visible' => ['sometimes', 'boolean'],
        ]));

        return new BrandResource($brand->fresh());
    }

    /** Products of the brand keep existing without a brand shown. */
    public function destroy(Brand $brand): Response
    {
        $brand->delete();

        return response()->noContent();
    }
}
