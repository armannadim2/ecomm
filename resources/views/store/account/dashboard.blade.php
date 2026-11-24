@extends('store.account.layout')

{{-- Variables: $customer --}}

@section('title', __('Mi cuenta'))

@section('account')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Hola, :name', ['name' => $customer->first_name]) }}</h1>
    <p class="mt-2 text-sm text-on-surface-variant">{{ __('Desde aquí puedes gestionar tus datos y direcciones. Tus pedidos aparecerán aquí cuando hagas tu primera compra.') }}</p>
@endsection
