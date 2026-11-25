{{-- Round page buttons (Stitch "Cerca i Catàleg"). Used as $paginator->links('store.partials.pagination'). --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Paginación') }}" class="flex flex-col items-center justify-between gap-4 rounded-2xl bg-white p-4 shadow-sm sm:flex-row">
        <p class="text-xs text-on-surface-variant">
            {!! __('Mostrando :from a :to de :total productos', [
                'from' => '<span class="font-bold text-primary">'.$paginator->firstItem().'</span>',
                'to' => '<span class="font-bold text-primary">'.$paginator->lastItem().'</span>',
                'total' => $paginator->total(),
            ]) !!}
        </p>
        <ul class="flex items-center gap-1.5">
            @php $btn = 'flex size-9 items-center justify-center rounded-full text-sm font-bold transition-colors'; @endphp
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $btn }} bg-surface-container-low text-outline/60" aria-disabled="true">@svg('heroicon-o-chevron-left', 'size-4')<span class="sr-only">{{ __('Anterior') }}</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} bg-surface-container text-primary hover:bg-surface-container-high">@svg('heroicon-o-chevron-left', 'size-4')<span class="sr-only">{{ __('Anterior') }}</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-1 font-bold text-outline">…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $btn }} bg-primary text-white" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $btn }} bg-surface-container text-primary hover:bg-surface-container-high">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} bg-surface-container text-primary hover:bg-surface-container-high">@svg('heroicon-o-chevron-right', 'size-4')<span class="sr-only">{{ __('Siguiente') }}</span></a>
                @else
                    <span class="{{ $btn }} bg-surface-container-low text-outline/60" aria-disabled="true">@svg('heroicon-o-chevron-right', 'size-4')<span class="sr-only">{{ __('Siguiente') }}</span></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
