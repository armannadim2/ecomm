@extends('store.layouts.app')

@section('title', __('Crear cuenta'))

@section('content')
    <div class="mx-auto max-w-md rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Crear una cuenta') }}</h1>

        <form method="post" action="{{ route('register') }}" class="mt-6 space-y-4">
            @csrf
            @include('store.partials.field', ['name' => 'first_name', 'label' => __('Nombre'), 'autocomplete' => 'given-name'])
            @include('store.partials.field', ['name' => 'last_name', 'label' => __('Apellidos'), 'autocomplete' => 'family-name'])
            @include('store.partials.field', ['name' => 'email', 'label' => __('Correo electrónico'), 'type' => 'email', 'autocomplete' => 'email'])
            @include('store.partials.field', ['name' => 'phone', 'label' => __('Teléfono'), 'type' => 'tel', 'autocomplete' => 'tel', 'required' => false,
                'help' => __('Para avisarte de la entrega.')])
            @include('store.partials.field', ['name' => 'password', 'label' => __('Contraseña'), 'type' => 'password', 'autocomplete' => 'new-password',
                'help' => __('Mínimo 12 caracteres, con letras y números.')])
            @include('store.partials.field', ['name' => 'password_confirmation', 'label' => __('Repite la contraseña'), 'type' => 'password', 'autocomplete' => 'new-password'])

            <label class="flex items-start gap-2 text-sm text-on-surface-variant">
                <input type="checkbox" name="accept_terms" value="1" required @checked(old('accept_terms')) class="mt-0.5 accent-secondary">
                <span>{!! __('He leído y acepto las :terms y la :privacy.', [
                    'terms' => '<a href="'.e(route('store.page', 'condiciones-generales')).'" class="font-semibold text-secondary underline-offset-2 hover:underline" target="_blank">'.e(__('condiciones')).'</a>',
                    'privacy' => '<a href="'.e(route('store.page', 'politica-de-privacidad')).'" class="font-semibold text-secondary underline-offset-2 hover:underline" target="_blank">'.e(__('política de privacidad')).'</a>',
                ]) !!}</span>
            </label>
            @error('accept_terms') <p class="text-sm text-error">{{ $message }}</p> @enderror

            {{-- GDPR: separate, unticked by default. --}}
            <label class="flex items-start gap-2 text-sm text-on-surface-variant">
                <input type="checkbox" name="marketing" value="1" @checked(old('marketing')) class="mt-0.5 accent-secondary">
                <span>{{ __('Quiero recibir ofertas y novedades por correo (puedes darte de baja cuando quieras).') }}</span>
            </label>

            <button type="submit" class="w-full rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Crear cuenta') }}</button>
        </form>

        <p class="mt-4 text-sm text-on-surface-variant">{{ __('¿Ya tienes cuenta?') }} <a href="{{ route('login') }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Entra') }}</a></p>
    </div>
@endsection
