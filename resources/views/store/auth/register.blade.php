@extends('store.auth.shell', ['authTab' => 'register'])

@section('title', __('Crear cuenta'))

@section('authForm')
    <div>
        <h1 class="text-2xl font-extrabold text-primary">{{ __('Crear una cuenta') }}</h1>
        <p class="mt-1 text-sm text-on-surface-variant">{{ __('En menos de un minuto. Es gratis.') }}</p>
    </div>

    <form method="post" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            @include('store.partials.field', ['name' => 'first_name', 'label' => __('Nombre'), 'autocomplete' => 'given-name'])
            @include('store.partials.field', ['name' => 'last_name', 'label' => __('Apellidos'), 'autocomplete' => 'family-name'])
        </div>
        @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'autocomplete' => 'email'])
        @include('store.partials.field', ['name' => 'phone', 'label' => __('Teléfono'), 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false,
            'help' => __('Para avisarte de la entrega.')])
        <div class="grid gap-4 sm:grid-cols-2">
            @include('store.partials.field', ['name' => 'password', 'label' => __('Contraseña'), 'type' => 'password', 'autocomplete' => 'new-password',
                'help' => __('Mínimo 12 caracteres, con letras y números.')])
            @include('store.partials.field', ['name' => 'password_confirmation', 'label' => __('Repite la contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])
        </div>

        <label class="flex items-start gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms')) class="mt-0.5 size-4 accent-secondary">
            <span>{!! __('He leído y acepto las :terms y la :privacy.', [
                'terms' => '<a href="'.e(route('store.page', 'condiciones-generales')).'" class="font-semibold text-secondary underline-offset-2 hover:underline" target="_blank">'.e(__('condiciones')).'</a>',
                'privacy' => '<a href="'.e(route('store.page', 'politica-de-privacidad')).'" class="font-semibold text-secondary underline-offset-2 hover:underline" target="_blank">'.e(__('política de privacidad')).'</a>',
            ]) !!}</span>
        </label>
        @error('accept_terms') <p class="text-sm text-error">{{ $message }}</p> @enderror

        {{-- GDPR: separate, unticked by default. --}}
        <label class="flex items-start gap-2 text-sm text-on-surface-variant">
            <input type="checkbox" name="marketing" value="1" @checked(old('marketing')) class="mt-0.5 size-4 accent-secondary">
            <span>{{ __('Quiero recibir ofertas y novedades por correo (puedes darte de baja cuando quieras).') }}</span>
        </label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-5 py-3.5 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">
            {{ __('Crear cuenta') }} @svg('heroicon-o-arrow-right', 'size-5')
        </button>
    </form>

    <p class="text-center text-sm text-on-surface-variant">{{ __('¿Ya tienes cuenta?') }} <a href="{{ route('login') }}" class="font-bold text-secondary underline-offset-2 hover:underline">{{ __('Entra') }}</a></p>
@endsection
