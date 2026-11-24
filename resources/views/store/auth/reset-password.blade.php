@extends('store.layouts.app')

{{-- Variables: $token, $email --}}

@section('title', __('Nueva contraseña'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Crea una nueva contraseña') }}</h1>

        <form method="post" action="{{ route('password.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'value' => $email, 'autocomplete' => 'email'])
            @include('store.partials.field', ['name' => 'password', 'label' => __('Nueva contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])
            @include('store.partials.field', ['name' => 'password_confirmation', 'label' => __('Repite la contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])
            <button type="submit" class="w-full rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Guardar contraseña') }}</button>
        </form>
    </div>
@endsection
