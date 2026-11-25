@extends('store.layouts.app')

{{--
    Login / register frame (Stitch "Club Veïns i Autenticació"): navy benefits panel + form with tabs.
    Only real benefits of the shop are listed (no loyalty points or social logins: the shop has neither).
    Child views pass $authTab ('login' | 'register') and fill @section('authForm').
--}}

@section('content')
    @php
        $benefits = [
            ['heroicon-o-building-storefront', __('Recogida gratis en la tienda'), __('Compra online y recógelo en :address cuando esté listo.', ['address' => $store->address_line ?: $store->city])],
            ['heroicon-o-qr-code', __('Tu código de recogida en el móvil'), __('Sin papeles: enseña el QR en el mostrador.')],
            ['heroicon-o-clipboard-document-list', __('Todos tus pedidos en un sitio'), __('Consulta el estado y el detalle de cada compra.')],
            ['heroicon-o-map-pin', __('Compra más rápido'), __('Guarda tus direcciones y datos para la próxima vez.')],
        ];
        $tab = 'flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2.5 text-sm font-bold transition-colors';
    @endphp
    <div class="grid overflow-hidden rounded-3xl bg-white shadow-xl lg:grid-cols-12">
        <div class="relative overflow-hidden bg-primary p-6 text-white sm:p-8 lg:col-span-5 lg:p-10">
            <div class="pointer-events-none absolute -bottom-20 -right-20 size-72 rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-accent-light">{{ __('Tu cuenta en :store', ['store' => $store->store_name]) }}</p>
                    <p class="mt-2 text-2xl font-extrabold leading-tight sm:text-3xl">{{ __('Compra fácil en tu tienda de barrio.') }}</p>
                </div>
                <ul class="hidden flex-col gap-3 sm:flex">
                    @foreach ($benefits as [$icon, $title, $text])
                        <li class="flex items-start gap-3 rounded-xl bg-white/10 p-3.5">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent/20 text-accent-light" aria-hidden="true">@svg($icon, 'size-[18px]')</span>
                            <span>
                                <span class="block text-sm font-bold">{{ $title }}</span>
                                <span class="mt-0.5 block text-xs text-white/75">{{ $text }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="p-6 sm:p-8 lg:col-span-7 lg:p-10">
            <div class="mx-auto flex w-full max-w-xl flex-col gap-5">
                <nav class="flex items-center rounded-full bg-surface-container-low p-1.5" aria-label="{{ __('Acceso') }}">
                    <a href="{{ route('login') }}" @if ($authTab === 'login') aria-current="page" @endif
                       @class([$tab, 'bg-primary text-white shadow-sm' => $authTab === 'login', 'text-on-surface-variant hover:text-primary' => $authTab !== 'login'])>
                        @svg('heroicon-o-arrow-right-on-rectangle', 'size-[18px]') {{ __('Entrar') }}
                    </a>
                    <a href="{{ route('register') }}" @if ($authTab === 'register') aria-current="page" @endif
                       @class([$tab, 'bg-primary text-white shadow-sm' => $authTab === 'register', 'text-on-surface-variant hover:text-primary' => $authTab !== 'register'])>
                        @svg('heroicon-o-user-plus', 'size-[18px]') {{ __('Crear cuenta') }}
                    </a>
                </nav>

                @yield('authForm')

                <ul class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 pt-2 text-xs text-on-surface-variant">
                    <li class="flex items-center gap-1.5">@svg('heroicon-o-lock-closed', 'size-4 text-success') {{ __('Conexión cifrada') }}</li>
                    <li class="flex items-center gap-1.5">@svg('heroicon-o-shield-check', 'size-4 text-accent') {{ __('Datos protegidos (RGPD)') }}</li>
                    @if ($store->address_line)
                        <li class="flex items-center gap-1.5">@svg('heroicon-o-building-storefront', 'size-4 text-primary') {{ $store->address_line }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection
