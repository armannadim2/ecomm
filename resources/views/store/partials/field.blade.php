{{--
    Simple labelled input with its validation error.
    Params: name, label, type (text), value (old() or given), autocomplete, required (true), help.
--}}
@php
    $type ??= 'text';
    $required ??= true;
    $value = $type === 'password' ? null : old($name, $value ?? null);
@endphp
<div>
    <label for="{{ $name }}" class="block text-sm font-bold text-primary">{{ $label }}@unless ($required) <span class="font-normal text-outline">({{ __('opcional') }})</span>@endunless</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
           @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
           @if ($required) required @endif
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
           class="mt-1 w-full rounded-xl border border-outline-variant bg-surface-container-low px-3.5 py-2.5 text-sm text-on-surface focus:border-primary focus:bg-white focus:outline-none">
    @if (! empty($help))
        <p class="mt-1 text-xs text-outline">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-sm text-error">{{ $message }}</p>
    @enderror
</div>
