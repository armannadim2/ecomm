{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('store.home') }}</loc></url>
@foreach ($categories as $category)
    <url><loc>{{ route('store.category', $category->slug) }}</loc><lastmod>{{ $category->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@foreach ($products as $product)
    <url><loc>{{ route('store.product', $product->slug) }}</loc><lastmod>{{ $product->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@foreach ($pages as $page)
    <url><loc>{{ route('store.page', $page->slug) }}</loc><lastmod>{{ $page->updated_at->toAtomString() }}</lastmod></url>
@endforeach
</urlset>
