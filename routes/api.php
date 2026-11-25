<?php

use App\Domain\Integrations\ApiScopes as S;
use App\Domain\Store\Settings\StoreSettings;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductImageController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\VariantController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Meta WhatsApp Cloud API webhook (customer service + AI assistant). URL to paste in Meta:
 * https://<shop>/api/webhooks/whatsapp — signed requests only (see WhatsAppWebhookController).
 */
Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->middleware('throttle:60,1')->name('webhooks.whatsapp.verify');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->middleware('throttle:600,1')->name('webhooks.whatsapp');

/*
 * REST API v1 for outside systems (POS, ERP, marketplaces). Reference: docs/API.md.
 * Auth: "Authorization: Bearer <key>" — keys are created by the owner in Integraciones → Claves API,
 * each with scopes (ApiScopes). 120 requests a minute per key.
 */
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'api.client', 'throttle:api-clients'])
    ->name('api.v1.')
    ->group(function () {
        // Connection check: who am I and what may I do.
        Route::get('/', fn (Request $request) => [
            'store' => app(StoreSettings::class)->store_name,
            'api_version' => 'v1',
            'client' => $request->user()->name,
            'scopes' => $request->user()->currentAccessToken()->abilities,
        ])->name('ping');

        Route::middleware('abilities:'.S::PRODUCTS_READ)->group(function () {
            Route::get('products', [ProductController::class, 'index'])->name('products.index');
            Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
            Route::get('variants', [VariantController::class, 'index'])->name('variants.index');
            Route::get('variants/{variant:ulid}', [VariantController::class, 'show'])->name('variants.show');
        });

        Route::middleware('abilities:'.S::PRODUCTS_WRITE)->group(function () {
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
            Route::post('products/{product}/publish', [ProductController::class, 'publish'])->name('products.publish');
            Route::post('products/{product}/unpublish', [ProductController::class, 'unpublish'])->name('products.unpublish');
            Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
            Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
            Route::post('products/{product}/variants', [VariantController::class, 'store'])->name('variants.store');
            Route::patch('variants/{variant:ulid}', [VariantController::class, 'update'])->name('variants.update');
            Route::delete('variants/{variant:ulid}', [VariantController::class, 'destroy'])->name('variants.destroy');
        });

        Route::middleware('abilities:'.S::STOCK_WRITE)->group(function () {
            Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
            Route::post('stock/set', [StockController::class, 'set'])->name('stock.set');
        });

        Route::middleware('abilities:'.S::CATEGORIES_READ)->group(function () {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
            Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
            Route::get('brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
        });

        Route::middleware('abilities:'.S::CATEGORIES_WRITE)->group(function () {
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::patch('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
            Route::patch('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
        });

        Route::middleware('abilities:'.S::ORDERS_READ)->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware('abilities:'.S::ORDERS_WRITE)->group(function () {
            Route::post('orders/{order}/status', [OrderController::class, 'changeStatus'])->name('orders.status');
            Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
        });

        Route::middleware('abilities:'.S::CUSTOMERS_READ)->group(function () {
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer:ulid}', [CustomerController::class, 'show'])->name('customers.show');
        });
    });
