@extends('store.account.layout')

{{-- Variables: $addresses (default first) --}}

@section('title', __('Mis direcciones'))

@section('account')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Mis direcciones') }}</h1>
        <a href="{{ route('account.addresses.create') }}" class="rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Añadir dirección') }}</a>
    </div>

    @forelse ($addresses as $address)
        <div class="mt-4 rounded-2xl border border-outline-variant/60 bg-surface-container-low p-4">
            @if ($address->is_default)<p class="text-xs font-bold uppercase tracking-wider text-secondary">{{ __('Dirección principal') }}</p>@endif
            <address class="text-sm not-italic text-primary">{!! collect($address->lines())->map(fn ($l) => e($l))->implode('<br>') !!}</address>
            <div class="mt-2 flex gap-4 text-sm">
                <a href="{{ route('account.addresses.edit', $address) }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Editar') }}</a>
                <form method="post" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm({{ Js::from(__('¿Eliminar esta dirección?')) }})">
                    @csrf @method('delete')
                    <button type="submit" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Eliminar') }}</button>
                </form>
            </div>
        </div>
    @empty
        <p class="mt-4 text-sm text-on-surface-variant">{{ __('Todavía no tienes direcciones guardadas.') }}</p>
    @endforelse
@endsection
