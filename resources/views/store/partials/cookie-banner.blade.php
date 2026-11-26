{{--
    Cookie banner (AEPD 2023): only shown when an analytics/advertising tag is configured.
    Decided in the browser from the tm_consent cookie, so the page can be cached for everybody.
    "Rechazar" is as easy as "Aceptar"; nothing but technical cookies loads before a choice.
    Variables: $analytics (AnalyticsSettings)
--}}
@if ($analytics->needsConsent())
    <div x-data="cookieConsent({
            version: {{ $analytics->consent_version }},
            endpoint: @js(route('cookies.consent')),
            ga4: @js($analytics->ga4_measurement_id),
            pixel: @js($analytics->meta_pixel_id),
         })"
         x-on:open-cookie-settings.window="open = true; details = true"
         x-show="open" x-cloak
         class="fixed inset-x-0 bottom-0 z-[60] p-3 sm:p-4 print:hidden"
         role="dialog" aria-modal="false" aria-labelledby="cookie-title">
        <div class="mx-auto max-w-3xl rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-outline-variant/70">
            <p id="cookie-title" class="text-base font-bold text-primary">{{ __('Usamos cookies') }}</p>
            <p class="mt-1 text-sm text-on-surface-variant">
                {{ __('Usamos cookies técnicas para que la tienda funcione y, si nos dejas, cookies de analítica y publicidad para mejorar la web y mostrarte ofertas.') }}
                <a href="{{ route('store.page', 'politica-de-cookies') }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Política de cookies') }}</a>
            </p>

            <div x-show="details" x-cloak class="mt-4 flex flex-col gap-2 text-sm">
                <label class="flex items-start gap-3 rounded-xl bg-surface-container-low p-3">
                    <input type="checkbox" checked disabled class="mt-0.5 size-4 accent-secondary">
                    <span><strong class="text-primary">{{ __('Técnicas') }}</strong> · {{ __('Necesarias: cesta, sesión, idioma y seguridad. Siempre activas.') }}</span>
                </label>
                @if ($analytics->ga4_measurement_id)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-surface-container-low p-3">
                        <input type="checkbox" x-model="choice.analytics" class="mt-0.5 size-4 accent-secondary">
                        <span><strong class="text-primary">{{ __('Analítica') }}</strong> · {{ __('Google Analytics: cuántas personas visitan la web y qué páginas ven.') }}</span>
                    </label>
                @endif
                @if ($analytics->meta_pixel_id)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-surface-container-low p-3">
                        <input type="checkbox" x-model="choice.marketing" class="mt-0.5 size-4 accent-secondary">
                        <span><strong class="text-primary">{{ __('Publicidad') }}</strong> · {{ __('Meta (Facebook e Instagram): medir nuestros anuncios y mostrarte ofertas.') }}</span>
                    </label>
                @endif
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="save(false, false)" class="rounded-xl border-2 border-primary px-5 py-2.5 text-sm font-bold text-primary hover:bg-surface-container-low">{{ __('Rechazar') }}</button>
                <button type="button" x-show="!details" x-on:click="details = true" class="rounded-xl border-2 border-primary px-5 py-2.5 text-sm font-bold text-primary hover:bg-surface-container-low">{{ __('Configurar') }}</button>
                <button type="button" x-show="details" x-cloak x-on:click="save(choice.analytics, choice.marketing)" class="rounded-xl border-2 border-primary px-5 py-2.5 text-sm font-bold text-primary hover:bg-surface-container-low">{{ __('Guardar selección') }}</button>
                <button type="button" x-on:click="save(true, true)" class="rounded-xl border-2 border-primary bg-primary px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-container">{{ __('Aceptar') }}</button>
            </div>
        </div>
    </div>
@endif
