{{--
    Official Tot & Més logo (brand guide "Concept 2 · La Bossa"; files in public/brand, from the logo kit).
    The artwork has its text in outlines: never recolour, stretch or add effects (brand rules), and keep
    clear space around it — so no tagline is drawn next to it.
    Expects: $name (alt text). Optional: $size ('md' header, 'sm' footer), $variant ('color' on light
    backgrounds, 'reversed' on navy or photos).
--}}
@php
    $size ??= 'md';
    $variant ??= 'color';
    // The horizontal logo is 639 × 154; the brand minimum on screen is 120 px wide.
    [$w, $h] = $size === 'md' ? [183, 44] : [158, 38];
@endphp
<img src="{{ asset('brand/totimes-horizontal-'.$variant.'.svg') }}" alt="{{ $name }}" width="{{ $w }}" height="{{ $h }}"
     @class(['block w-auto', 'h-10 sm:h-11' => $size === 'md', 'h-9' => $size === 'sm'])>
