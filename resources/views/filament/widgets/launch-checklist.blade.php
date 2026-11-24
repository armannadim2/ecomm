<x-filament-widgets::widget>
    <x-filament::section
        heading="Pon tu tienda en marcha"
        description="Completa estos pasos antes de abrir la tienda al público."
        icon="heroicon-o-rocket-launch"
    >
        <ul class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($steps as $step)
                <li class="flex items-center gap-3 py-3">
                    @if ($step['done'])
                        <x-filament::icon icon="heroicon-s-check-circle" class="h-6 w-6 text-success-600" />
                    @else
                        <x-filament::icon icon="heroicon-o-ellipsis-horizontal-circle" class="h-6 w-6 text-gray-400" />
                    @endif

                    <div class="flex-1">
                        <p @class(['font-medium', 'line-through text-gray-400' => $step['done']])>{{ $step['label'] }}</p>
                        <p class="text-sm text-gray-500">{{ $step['help'] }}</p>
                    </div>

                    @unless ($step['done'])
                        <x-filament::button tag="a" :href="$step['url']" size="sm">Ir</x-filament::button>
                    @endunless
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
