{{--
    Floating "Chat on WhatsApp" button (customer service + AI assistant). A plain wa.me link with a
    prefilled message: no WhatsApp/Meta script or cookie is loaded until the customer taps it.
    Hidden in the visual editor preview and when the shop has no phone number.
--}}
@php
    $waUrl = \App\Domain\Content\Blocks\Block::resolveUrl('[whatsapp]');
    $editing = \App\Domain\Content\Blocks\PageEditing::active();
@endphp
@if ($waUrl && ! $editing)
    <a href="{{ $waUrl }}?text={{ rawurlencode(__('¡Hola! Tengo una pregunta sobre la tienda.')) }}"
       target="_blank" rel="noopener"
       class="fixed bottom-4 right-4 z-40 flex items-center gap-2 rounded-full bg-[#1f8f4e] py-3 pl-3 pr-4 text-sm font-bold text-white shadow-lg transition-transform hover:scale-105 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#1f8f4e]/40 motion-reduce:transition-none sm:bottom-6 sm:right-6"
       aria-label="{{ __('Escríbenos por WhatsApp (se abre en una pestaña nueva)') }}">
        <svg viewBox="0 0 32 32" class="size-6 shrink-0" fill="currentColor" aria-hidden="true" focusable="false">
            <path d="M16 3C8.8 3 3 8.7 3 15.8c0 2.3.6 4.5 1.8 6.4L3 29l7-1.8c1.8 1 3.9 1.5 6 1.5 7.2 0 13-5.7 13-12.8S23.2 3 16 3Zm0 23.4c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-4.1 1.1 1.1-4-.3-.4c-1.1-1.7-1.6-3.6-1.6-5.6C5.3 10 10.1 5.3 16 5.3S26.7 10 26.7 15.8 21.9 26.4 16 26.4Zm5.9-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.6.1-.2.1-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.8 5 .8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4Z"/>
        </svg>
        <span class="hidden sm:inline">{{ __('¿Hablamos?') }}</span>
        <span class="sm:hidden">{{ __('WhatsApp') }}</span>
    </a>
@endif
