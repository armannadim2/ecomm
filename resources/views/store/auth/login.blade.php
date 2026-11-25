@extends('store.auth.shell', ['authTab' => 'login'])

@section('title', __('Entrar'))

@section('authForm')
    <div>
        <h1 class="text-2xl font-extrabold text-primary">{{ __('Hola de nuevo') }}</h1>
        <p class="mt-1 text-sm text-on-surface-variant">{{ __('Entra para ver tus pedidos, tu código de recogida y tus direcciones.') }}</p>
    </div>

    <form method="post" action="{{ route('login') }}" class="space-y-4">
        @csrf
        @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'autocomplete' => 'email'])
        <div>
            @include('store.partials.field', ['name' => 'password', 'label' => __('Contraseña'), 'type' => 'password', 'autocomplete' => 'current-password'])
            <p class="mt-1.5 text-right text-xs"><a href="{{ route('password.request') }}" class="font-bold text-secondary underline-offset-2 hover:underline">{{ __('¿Has olvidado tu contraseña?') }}</a></p>
        </div>

        <label class="flex items-center gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="remember" value="1" class="size-4 accent-secondary"> {{ __('Recordarme en este dispositivo') }}
        </label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-5 py-3.5 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">
            {{ __('Entrar') }} @svg('heroicon-o-arrow-right', 'size-5')
        </button>
    </form>

    <p class="text-center text-sm text-on-surface-variant">{{ __('¿No tienes cuenta?') }} <a href="{{ route('register') }}" class="font-bold text-secondary underline-offset-2 hover:underline">{{ __('Regístrate') }}</a></p>
@endsection
