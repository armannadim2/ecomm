@extends('store.layouts.app')

@section('title', __('Recuperar contraseña'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('¿Has olvidado tu contraseña?') }}</h1>
        <p class="mt-2 text-sm text-on-surface-variant">{{ __('Escribe tu correo y te enviaremos un enlace para crear una nueva.') }}</p>

        <form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'autocomplete' => 'email'])
            <button type="submit" class="w-full rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Enviar enlace') }}</button>
        </form>
    </div>
@endsection
