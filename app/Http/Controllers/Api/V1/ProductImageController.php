<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Product;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * /api/v1/products/{id}/images — multipart upload (field "image"). Images are not fetched
 * from URLs on purpose: the server must not download addresses chosen by the caller.
 */
class ProductImageController extends ApiController
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240', 'dimensions:max_width=8000,max_height=8000'],
            'alt' => ['nullable', 'string', 'max:200'],
        ]);

        $product->addMediaFromRequest('image')
            ->usingFileName(str($product->slug)->limit(60, '').'-'.now()->format('YmdHis').'.'.$request->file('image')->extension())
            ->withCustomProperties(array_filter(['alt' => $data['alt'] ?? null]))
            ->toMediaCollection(Product::IMAGES);

        return (new ProductResource($product->fresh(['brand', 'categories', 'variants', 'media'])))->response()->setStatusCode(201);
    }

    public function destroy(Product $product, string $image): Response
    {
        $media = $product->getMedia(Product::IMAGES)->firstWhere('uuid', $image);
        abort_if($media === null, 404, 'No existe esa imagen en el producto.');

        $media->delete();

        return response()->noContent();
    }
}
