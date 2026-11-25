{{-- Visual editor side panel: form of the selected block. Changes are applied when a field loses focus. --}}
<div x-data x-on:change.debounce.300ms="$wire.apply()" x-on:pb-apply.window="$wire.apply()">
    {{ $this->form }}
    <x-filament-actions::modals />
</div>
