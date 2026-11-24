@extends('store.layouts.app')

@section('title', __('Entrar'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Entrar en tu cuenta') }}</h1>

        <form method="post" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'autocomplete' => 'email'])
            @include('store.partials.field', ['name' => 'password', 'label' => __('Contraseña'), 'type' => 'password', 'autocomplete' => 'current-password'])

            <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                <input type="checkbox" name="remember" value="1" class="accent-secondary"> {{ __('Recordarme en este dispositivo') }}
            </label>

            <button type="submit" class="w-full rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Entrar') }}</button>
        </form>

        <p class="mt-4 text-sm"><a href="{{ route('password.request') }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('¿Has olvidado tu contraseña?') }}</a></p>
        <p class="mt-2 text-sm text-on-surface-variant">{{ __('¿No tienes cuenta?') }} <a href="{{ route('register') }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Regístrate') }}</a></p>
    </div>
@endsection
