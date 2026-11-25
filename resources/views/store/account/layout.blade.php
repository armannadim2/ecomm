@extends('store.layouts.app')

{{-- "Mi cuenta" wrapper (Stitch "El Meu Compte": side navigation + content). Child views fill @section('account'). --}}

@section('content')
    @php
        $accountLinks = [
            ['account.dashboard', 'heroicon-o-shopping-bag', __('Mis pedidos'), 'account.dashboard'],
            ['account.profile', 'heroicon-o-user', __('Mis datos'), 'account.profile'],
            ['account.returns', 'heroicon-o-arrow-uturn-left', __('Devoluciones y cambios'), 'account.returns*'],
            ['account.addresses', 'heroicon-o-map-pin', __('Mis direcciones'), 'account.addresses*'],
            ['account.privacy', 'heroicon-o-shield-check', __('Privacidad y datos'), 'account.privacy'],
        ];
    @endphp
    <nav aria-label="{{ __('Ruta') }}" class="mb-4 flex items-center gap-1.5 text-xs text-on-surface-variant">
        <a href="{{ route('store.home') }}" class="hover:text-secondary">{{ __('Inicio') }}</a>
        @svg('heroicon-o-chevron-right', 'size-3 text-outline')
        <a href="{{ route('account.dashboard') }}" class="font-bold text-primary">{{ __('Mi cuenta') }}</a>
    </nav>

    <div class="grid items-start gap-6 lg:grid-cols-12">
        <aside class="flex flex-col gap-4 lg:col-span-4 xl:col-span-3">
            <nav aria-label="{{ __('Mi cuenta') }}" class="rounded-2xl bg-white p-3 shadow-sm">
                <h2 class="px-3 pb-2 pt-1 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Gestión de la cuenta') }}</h2>
                <ul class="flex flex-col gap-1 text-sm">
                    @foreach ($accountLinks as [$route, $icon, $label, $pattern])
                        @php $current = request()->routeIs($pattern); @endphp
                        <li>
                            <a href="{{ route($route) }}" @if ($current) aria-current="page" @endif
                               @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold transition-colors',
                                   'bg-primary text-white' => $current,
                                   'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' => ! $current])>
                                @svg($icon, 'size-5 shrink-0') {{ $label }}
                            </a>
                        </li>
                    @endforeach
                    <li>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left font-semibold text-on-surface-variant hover:bg-surface-container-low hover:text-primary">
                                @svg('heroicon-o-arrow-left-on-rectangle', 'size-5 shrink-0') {{ __('Salir') }}
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
            @include('store.account.partials.help')
        </aside>
        <div class="flex flex-col gap-6 lg:col-span-8 xl:col-span-9">
            @hasSection('accountRaw')
                @yield('accountRaw')
            @else
                <div class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                    @yield('account')
                </div>
            @endif
        </div>
    </div>
@endsection
