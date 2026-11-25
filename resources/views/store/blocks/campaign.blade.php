{{-- Block "Campaña activa": shows the live campaign (Black Friday, Navidad...) when there is one. --}}
@if ($campaign)
    <section class="mx-auto w-full max-w-[1280px] px-4 py-4 lg:px-12">
        @include('store.partials.campaign-hero', ['campaign' => $campaign])
    </section>
@elseif (\App\Domain\Content\Blocks\PageEditing::active())
    <section class="mx-auto w-full max-w-[1280px] px-4 py-4 lg:px-12">
        <div class="rounded-3xl border-2 border-dashed border-outline-variant bg-white/60 p-8 text-center text-sm font-semibold text-on-surface-variant">
            {{ __('Aquí aparecerá el banner de la campaña activa (Black Friday, Navidad…). Ahora no hay ninguna.') }}
        </div>
    </section>
@endif
