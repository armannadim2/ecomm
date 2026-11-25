{{--
    HTML e-mail layout (Stitch "Email Receipt & Factura" / "Comanda a Punt per Recollir").
    E-mail clients ignore stylesheets and modern CSS, so everything is tables with inline styles.
    Sections: preheader (inbox preview text), content. Variables: $store (StoreSettings).
    Colours are the storefront tokens (resources/css/app.css).
--}}
@php
    $font = "'Bricolage Grotesque', 'Segoe UI', Arial, Helvetica, sans-serif";
    $legalPages = \App\Domain\Content\Models\Page::query()->published()
        ->where('type', \App\Domain\Content\Models\Page::TYPE_LEGAL)->orderBy('position')->get(['title', 'slug']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>@yield('title')</title>
    <style>
        @media (max-width: 620px) {
            .tm-pad { padding: 20px !important; }
            .tm-col { display: block !important; width: 100% !important; box-sizing: border-box; }
            .tm-hide-sm { display: none !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#fdf1ec;font-family:{{ $font }};color:#1d3557;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdf1ec;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    {{-- Brand stripe --}}
                    <tr><td style="height:8px;line-height:8px;font-size:0;background:#1d3557;background-image:linear-gradient(90deg,#1d3557,#c4431c,#e4572e);">&nbsp;</td></tr>
                    <tr>
                        <td class="tm-pad" style="padding:32px 40px;">
                            @yield('content')
                        </td>
                    </tr>
                    {{-- Footer --}}
                    <tr>
                        <td style="background:#f5e1d8;padding:24px 32px;text-align:center;font-size:12px;line-height:18px;color:#4a5b76;">
                            <p style="margin:0 0 8px;">
                                <strong style="color:#1d3557;">{{ $store->store_name }}</strong>
                                @if ($store->address_line) · {{ $store->fullAddress() }}@endif
                            </p>
                            @if ($store->email || $store->phone)
                                <p style="margin:0 0 8px;">
                                    @if ($store->email)<a href="mailto:{{ $store->email }}" style="color:#1d3557;">{{ $store->email }}</a>@endif
                                    @if ($store->email && $store->phone) · @endif
                                    @if ($store->phone){{ __('Teléfono y WhatsApp') }}: {{ $store->phone }}@endif
                                </p>
                            @endif
                            @if ($store->legal_name || $store->nif)
                                <p style="margin:0 0 8px;">{{ collect([$store->legal_name, $store->nif ? __('NIF').': '.$store->nif : null])->filter()->implode(' · ') }}</p>
                            @endif
                            <p style="margin:0 0 8px;">@yield('footer_note')</p>
                            @if ($legalPages->isNotEmpty())
                                <p style="margin:0;">
                                    @foreach ($legalPages as $page)
                                        <a href="{{ route('store.page', $page->slug) }}" style="color:#4a5b76;text-decoration:underline;">{{ $page->title }}</a>@if (! $loop->last) &nbsp;·&nbsp; @endif
                                    @endforeach
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
