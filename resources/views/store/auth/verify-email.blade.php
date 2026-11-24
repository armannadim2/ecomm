@extends('store.layouts.app')

@section('title', __('Verifica tu correo'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Revisa tu correo') }}</h1>
        <p class="mt-2 text-sm text-on-surface-variant">{!! __('Te hemos enviado un enlace a :email. Pulsa en él para activar tu cuenta.', ['email' => '<strong>'.e(auth('web')->user()->email).'</strong>']) !!}</p>

        <form method="post" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-surface-container-low">{{ __('Enviar el enlace otra vez') }}</button>
        </form>

        <form method="post" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Salir') }}</button>
        </form>
    </div>
@endsection
