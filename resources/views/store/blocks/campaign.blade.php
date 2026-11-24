{{-- Block "Campaña activa": shows the live campaign (Black Friday, Navidad...) when there is one. --}}
@if ($campaign)
    <section class="mx-auto w-full max-w-[1280px] px-4 py-4 lg:px-12">
        @include('store.partials.campaign-hero', ['campaign' => $campaign])
    </section>
@endif
