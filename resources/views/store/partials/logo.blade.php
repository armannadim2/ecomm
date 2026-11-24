{{--
    Tot & Més logo (Stitch "Tot & Més Logo", D:\Projects\eComm\design\totmes-logo.svg).
    Bag icon as inline SVG + the store name as live text, so it uses the self-hosted font and follows
    a rename in "Datos de la tienda". An "&" in the name is painted in the brand orange, like the logo.
    Expects: $name. Optional: $tagline (small caps line under the name), $size ('md' header, 'sm' footer).
--}}
@php
    $size ??= 'md';
    $tagline ??= null;
    $parts = preg_split('/(\s*&\s*)/u', $name, 2, PREG_SPLIT_DELIM_CAPTURE);
@endphp
<span class="flex items-center gap-2.5">
    <svg viewBox="16 20 78 82" @class(['shrink-0', 'size-11' => $size === 'md', 'size-9' => $size === 'sm']) aria-hidden="true" focusable="false">
        <g transform="translate(10 10)">
            <path d="M32 30C32 12 58 12 58 30" stroke="#1B2942" stroke-width="8" stroke-linecap="round" fill="none"/>
            <rect x="10" y="26" width="70" height="64" rx="16" fill="#1B2942"/>
            <path d="M23 48 30 41 37 48V57H23Z" fill="#E05A2B"/>
            <path d="M53 41C63 43 65 52 65 57 55 57 51 51 53 41Z" fill="#FFFFFF"/>
            <circle cx="30" cy="72" r="7" fill="#FFFFFF"/>
            <path d="M58 64 60 70H66L61 74 63 80 58 76 53 80 55 74 50 70H56Z" fill="#E05A2B"/>
        </g>
    </svg>
    <span class="flex flex-col">
        <span @class([
            'font-extrabold leading-none tracking-tight text-primary',
            'text-[26px]' => $size === 'md',
            'text-[22px]' => $size === 'sm',
        ])>
            @if (count($parts) === 3)
                {{ $parts[0] }} <span class="text-accent">&amp;</span> {{ $parts[2] }}
            @else
                {{ $name }}
            @endif
        </span>
        @if ($tagline)
            <span class="mt-1 text-[10px] font-semibold uppercase leading-none tracking-[0.3em] text-outline">{{ $tagline }}</span>
        @endif
    </span>
</span>
