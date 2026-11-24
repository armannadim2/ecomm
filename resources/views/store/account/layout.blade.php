@extends('store.layouts.app')

{{-- "Mi cuenta" wrapper. Child views fill @section('account') --}}

@section('content')
    <div class="grid gap-8 md:grid-cols-4">
        <nav aria-label="{{ __('Mi cuenta') }}" class="h-fit space-y-1 rounded-2xl border border-outline-variant/60 bg-white p-3 text-sm shadow-sm">
            <a href="{{ route('account.dashboard') }}" class="block rounded-xl px-3 py-2 font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">{{ __('Resumen') }}</a>
            <a href="{{ route('account.profile') }}" class="block rounded-xl px-3 py-2 font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">{{ __('Mis datos') }}</a>
            <a href="{{ route('account.addresses') }}" class="block rounded-xl px-3 py-2 font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">{{ __('Mis direcciones') }}</a>
            <a href="{{ route('account.privacy') }}" class="block rounded-xl px-3 py-2 font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">{{ __('Privacidad') }}</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left block rounded-xl px-3 py-2 font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">{{ __('Salir') }}</button>
            </form>
        </nav>
        <div class="rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-8 md:col-span-3">
            @yield('account')
        </div>
    </div>
@endsection
