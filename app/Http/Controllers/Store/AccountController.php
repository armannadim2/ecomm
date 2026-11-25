<?php

namespace App\Http\Controllers\Store;

use App\Domain\Content\Blocks\Block;
use App\Domain\Customers\Actions\DeleteCustomerAccount;
use App\Domain\Customers\Actions\ExportCustomerData;
use App\Domain\Customers\Actions\SaveAddress;
use App\Domain\Customers\Models\Address;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddressRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Mi cuenta": profile, addresses and privacy (GDPR) for logged-in customers.
 */
class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $orders = $customer->orders()->with('items')->withCount('items')->limit(20)->get();

        // The pickup order to show first: ready at the counter, else the newest one still open.
        $active = $orders->first(fn (Order $o) => $o->fulfillment === Fulfillment::Pickup && $o->status === OrderStatus::ReadyForPickup)
            ?? $orders->first(fn (Order $o) => $o->status->isOpen());

        return view('store.account.dashboard', [
            'customer' => $customer,
            'activeOrder' => $active,
            'orders' => $active ? $orders->reject(fn (Order $o) => $o->is($active))->values() : $orders,
            'address' => $customer->addresses()->first(),
            'openingHours' => (new Block('hours', ['h' => app(CheckoutSettings::class)->opening_hours]))->text('h'),
        ]);
    }

    public function editProfile(Request $request): View
    {
        return view('store.account.profile', ['customer' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'marketing' => ['nullable', 'boolean'],
        ]);

        $emailChanged = mb_strtolower($data['email']) !== $customer->email;

        $customer->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
        ]);
        $customer->marketing_consent_at = $request->boolean('marketing')
            ? ($customer->marketing_consent_at ?? now())
            : null;

        if ($emailChanged) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        if ($emailChanged) {
            $customer->sendEmailVerificationNotification();
        }

        return back()->with('status', $emailChanged
            ? __('Datos guardados. Te hemos enviado un enlace para verificar el nuevo correo.')
            : __('Datos guardados.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->string('password')->toString()]);

        return back()->with('status', __('Contraseña actualizada.'));
    }

    public function addresses(Request $request): View
    {
        return view('store.account.addresses.index', ['addresses' => $request->user()->addresses]);
    }

    public function createAddress(): View
    {
        return view('store.account.addresses.form', ['address' => new Address]);
    }

    public function storeAddress(AddressRequest $request, SaveAddress $save): RedirectResponse
    {
        $save->handle($request->user(), $request->addressData());

        return redirect()->route('account.addresses')->with('status', __('Dirección guardada.'));
    }

    public function editAddress(Request $request, string $address): View
    {
        return view('store.account.addresses.form', ['address' => $this->ownAddress($request, $address)]);
    }

    public function updateAddress(AddressRequest $request, string $address, SaveAddress $save): RedirectResponse
    {
        $save->handle($request->user(), $request->addressData(), $this->ownAddress($request, $address));

        return redirect()->route('account.addresses')->with('status', __('Dirección actualizada.'));
    }

    public function destroyAddress(Request $request, string $address): RedirectResponse
    {
        $model = $this->ownAddress($request, $address);
        $wasDefault = $model->is_default;
        $model->delete();

        if ($wasDefault) {
            $request->user()->addresses()->first()?->update(['is_default' => true]);
        }

        return redirect()->route('account.addresses')->with('status', __('Dirección eliminada.'));
    }

    public function privacy(): View
    {
        return view('store.account.privacy');
    }

    public function exportData(Request $request, ExportCustomerData $export): StreamedResponse
    {
        $data = $export->handle($request->user());

        return response()->streamDownload(
            fn () => print (json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'mis-datos.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function destroyAccount(Request $request, DeleteCustomerAccount $delete): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']]);

        $customer = $request->user();

        Auth::guard('web')->logout();
        $delete->handle($customer);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('store.home')->with('status', __('Tu cuenta y tus datos personales se han eliminado.'));
    }

    /** Customers can only ever reach their own addresses (prevents IDOR). */
    private function ownAddress(Request $request, string $ulid): Address
    {
        return $request->user()->addresses()->where('ulid', $ulid)->firstOrFail();
    }
}
