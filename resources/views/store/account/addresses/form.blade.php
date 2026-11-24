@extends('store.account.layout')

{{-- Variables: $address (new or existing). Province is filled automatically from the postal code. --}}

@section('title', $address->exists ? __('Editar dirección') : __('Nueva dirección'))

@section('account')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $address->exists ? __('Editar dirección') : __('Nueva dirección') }}</h1>

    <form method="post" action="{{ $address->exists ? route('account.addresses.update', $address) : route('account.addresses.store') }}"
          class="mt-6 max-w-md space-y-4">
        @csrf
        @if ($address->exists) @method('put') @endif

        @include('store.partials.field', ['name' => 'first_name', 'label' => __('Nombre'), 'value' => $address->first_name, 'autocomplete' => 'given-name'])
        @include('store.partials.field', ['name' => 'last_name', 'label' => __('Apellidos'), 'value' => $address->last_name, 'autocomplete' => 'family-name'])
        @include('store.partials.field', ['name' => 'line1', 'label' => __('Dirección'), 'value' => $address->line1, 'autocomplete' => 'address-line1',
            'help' => __('Calle y número.')])
        @include('store.partials.field', ['name' => 'line2', 'label' => __('Piso, puerta…'), 'value' => $address->line2, 'required' => false, 'autocomplete' => 'address-line2'])
        @include('store.partials.field', ['name' => 'postal_code', 'label' => __('Código postal'), 'value' => $address->postal_code, 'autocomplete' => 'postal-code',
            'help' => __('Enviamos a la Península y Baleares.')])
        @include('store.partials.field', ['name' => 'city', 'label' => __('Población'), 'value' => $address->city, 'autocomplete' => 'address-level2'])
        @include('store.partials.field', ['name' => 'phone', 'label' => __('Teléfono'), 'type' => 'tel', 'value' => $address->phone, 'autocomplete' => 'tel',
            'help' => __('Para que el transportista pueda contactarte.')])

        <details @if ($address->company || $address->tax_id) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-primary">{{ __('¿Necesitas factura a nombre de una empresa?') }}</summary>
            <div class="mt-4 space-y-4">
                @include('store.partials.field', ['name' => 'company', 'label' => __('Empresa'), 'value' => $address->company, 'required' => false])
                @include('store.partials.field', ['name' => 'tax_id', 'label' => __('NIF / CIF'), 'value' => $address->tax_id, 'required' => false])
            </div>
        </details>

        <label class="flex items-center gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default)) class="accent-secondary"> {{ __('Usar como dirección principal') }}
        </label>

        <button type="submit" class="rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Guardar dirección') }}</button>
    </form>
@endsection
