@extends('store.account.layout')

@section('title', __('Privacidad'))

@section('account')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Privacidad y datos') }}</h1>

    <section class="mt-6">
        <h2 class="text-lg font-extrabold text-primary">{{ __('Descargar mis datos') }}</h2>
        <p class="mt-1 text-sm text-on-surface-variant">{{ __('Recibirás un archivo con toda la información que guardamos sobre ti.') }}</p>
        <form method="post" action="{{ route('account.export') }}" class="mt-3">
            @csrf
            <button type="submit" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-surface-container-low">{{ __('Descargar mis datos') }}</button>
        </form>
    </section>

    <section class="mt-10">
        <h2 class="text-lg font-extrabold text-primary">{{ __('Eliminar mi cuenta') }}</h2>
        <p class="mt-1 text-sm text-on-surface-variant">{{ __('Borraremos tus datos personales y direcciones. Las facturas de compras anteriores se conservan de forma anónima durante el plazo que exige la ley. Esta acción no se puede deshacer.') }}</p>
        <form method="post" action="{{ route('account.destroy') }}" class="mt-3 max-w-md space-y-4"
              onsubmit="return confirm({{ Js::from(__('¿Seguro que quieres eliminar tu cuenta? No se puede deshacer.')) }})">
            @csrf @method('delete')
            @include('store.partials.field', ['name' => 'password', 'label' => __('Escribe tu contraseña para confirmar'), 'type' => 'password', 'autocomplete' => 'current-password'])
            <button type="submit" class="rounded-xl bg-error px-5 py-3 text-sm font-bold text-white">{{ __('Eliminar mi cuenta') }}</button>
        </form>
    </section>
@endsection
