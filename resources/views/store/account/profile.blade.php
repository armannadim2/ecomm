@extends('store.account.layout')

{{-- Variables: $customer --}}

@section('title', __('Mis datos'))

@section('account')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Mis datos') }}</h1>

    <form method="post" action="{{ route('account.profile.update') }}" class="mt-6 max-w-md space-y-4">
        @csrf @method('put')
        @include('store.partials.field', ['name' => 'first_name', 'label' => __('Nombre'), 'value' => $customer->first_name])
        @include('store.partials.field', ['name' => 'last_name', 'label' => __('Apellidos'), 'value' => $customer->last_name])
        @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'value' => $customer->email])
        @include('store.partials.field', ['name' => 'phone', 'label' => __('Teléfono'), 'type' => 'tel', 'value' => $customer->phone, 'required' => false])
        <label class="flex items-start gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="marketing" value="1" @checked(old('marketing', $customer->hasMarketingConsent())) class="mt-0.5 accent-secondary">
            <span>{{ __('Quiero recibir ofertas y novedades por correo.') }}</span>
        </label>
        <button type="submit" class="rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Guardar') }}</button>
    </form>

    <h2 class="mt-10 text-xl font-extrabold text-primary">{{ __('Cambiar contraseña') }}</h2>
    <form method="post" action="{{ route('account.password.update') }}" class="mt-4 max-w-md space-y-4">
        @csrf @method('put')
        @include('store.partials.field', ['name' => 'current_password', 'label' => __('Contraseña actual'), 'type' => 'password', 'autocomplete' => 'current-password'])
        @include('store.partials.field', ['name' => 'password', 'label' => __('Nueva contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])
        @include('store.partials.field', ['name' => 'password_confirmation', 'label' => __('Repite la nueva contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])
        <button type="submit" class="rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Cambiar contraseña') }}</button>
    </form>
@endsection
