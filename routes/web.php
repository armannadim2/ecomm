<?php

use App\Http\Controllers\Admin\OrderMailPreviewController;
use App\Http\Controllers\Admin\PageEditorPreviewController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\AuthController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CatalogController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\LocaleController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ReturnRequestController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Http\Middleware\SetUpPanel;
use Illuminate\Support\Facades\Route;

/*
| Storefront (Spanish URLs). Route names are English and stable: views and
| the future storefront design refer to names, never to hard-coded paths.
*/

Route::get('/', [CatalogController::class, 'home'])->name('store.home');
Route::get('/categorias', [CatalogController::class, 'categories'])->name('store.categories');
Route::get('/categoria/{slug}', [CatalogController::class, 'category'])->name('store.category');
Route::get('/producto/{slug}', [CatalogController::class, 'product'])->name('store.product');
Route::get('/buscar', [CatalogController::class, 'search'])->name('store.search')->middleware('throttle:60,1');
Route::get('/pagina/{slug}', [PageController::class, 'show'])->name('store.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('store.sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('store.robots');
Route::get('/idioma/{locale}', LocaleController::class)->name('locale.switch');

// Cart and checkout
Route::get('/cesta', [CartController::class, 'show'])->name('cart.show');
Route::post('/cesta', [CartController::class, 'add'])->name('cart.add')->middleware('throttle:60,1');
Route::patch('/cesta/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cesta/{variant}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cesta', [CartController::class, 'clear'])->name('cart.clear');
Route::post('/cesta/confirmar', [CheckoutController::class, 'store'])->name('checkout.place')->middleware('throttle:10,1');
Route::get('/pedido/{order}', [CheckoutController::class, 'show'])->name('order.show');

// Guests only
Route::middleware('guest:web')->prefix('cuenta')->group(function () {
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/acceder', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/acceder', [AuthController::class, 'login']);
    Route::get('/recuperar', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/recuperar', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/restablecer/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/restablecer', [AuthController::class, 'resetPassword'])->name('password.store');
});

// Logged-in customers
Route::middleware('auth:web')->prefix('cuenta')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');

    Route::get('/verificar', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::get('/verificar/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/verificar/reenviar', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('account.dashboard');
        Route::get('/datos', [AccountController::class, 'editProfile'])->name('account.profile');
        Route::put('/datos', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/contrasena', [AccountController::class, 'updatePassword'])->name('account.password.update');

        Route::get('/direcciones', [AccountController::class, 'addresses'])->name('account.addresses');
        Route::get('/direcciones/nueva', [AccountController::class, 'createAddress'])->name('account.addresses.create');
        Route::post('/direcciones', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
        Route::get('/direcciones/{address}', [AccountController::class, 'editAddress'])->name('account.addresses.edit');
        Route::put('/direcciones/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
        Route::delete('/direcciones/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');

        Route::get('/devoluciones', [ReturnRequestController::class, 'index'])->name('account.returns');
        Route::get('/devoluciones/nueva', [ReturnRequestController::class, 'create'])->name('account.returns.create');
        Route::post('/devoluciones', [ReturnRequestController::class, 'store'])->middleware('throttle:10,1')->name('account.returns.store');
        Route::get('/devoluciones/{returnRequest}', [ReturnRequestController::class, 'show'])->name('account.returns.show');

        Route::get('/privacidad', [AccountController::class, 'privacy'])->name('account.privacy');
        Route::post('/privacidad/exportar', [AccountController::class, 'exportData'])
            ->middleware('throttle:3,1')->name('account.export');
        Route::delete('/', [AccountController::class, 'destroyAccount'])->name('account.destroy');
    });
});

// Visual page editor: live preview of the staff member's draft (iframe inside the admin).
Route::get('/admin/editor/vista-previa/{page}', PageEditorPreviewController::class)
    ->middleware([SetUpPanel::class.':admin', FilamentAuthenticate::class])
    ->name('admin.page-editor.preview');

// Order e-mails as the customer sees them (staff only; ?locale=es|ca|en).
Route::get('/admin/pedidos/{order}/correo/{mail}', OrderMailPreviewController::class)
    ->middleware([SetUpPanel::class.':admin', FilamentAuthenticate::class])
    ->name('admin.order-mail.preview');
