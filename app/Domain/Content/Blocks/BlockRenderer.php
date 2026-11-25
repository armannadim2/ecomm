<?php

namespace App\Domain\Content\Blocks;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Queries\StorefrontProducts;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns stored page-builder blocks into Block objects with the data each one needs
 * (products, categories...). Unknown and hidden blocks are skipped.
 */
final class BlockRenderer
{
    /**
     * @param  array<int, array{type?: string, data?: array<string, mixed>}>|null  $raw
     * @return list<Block>
     */
    public function prepare(?array $raw, bool $includeHidden = false): array
    {
        $blocks = [];

        foreach (array_values($raw ?? []) as $index => $item) {
            $type = $item['type'] ?? null;
            $data = $item['data'] ?? [];

            if (! in_array($type, BlockTypes::TYPES, true) || (! $includeHidden && ! empty($data['is_hidden']))) {
                continue;
            }

            $block = new Block($type, $data, $type.'-'.$index, $index.'.');
            $block->index = $index;
            $block->payload = $this->payload($block);
            $blocks[] = $block;
        }

        return $blocks;
    }

    /** @return array<string, mixed> */
    private function payload(Block $block): array
    {
        return match ($block->type) {
            BlockTypes::SERVICE_STRIP => ['categories' => $this->topCategories(4)],
            BlockTypes::HERO => ['products' => $block->get('mosaic', 'newest') === 'newest'
                ? StorefrontProducts::sort(StorefrontProducts::query(), 'novedades')->limit(4)->get()
                : new Collection],
            BlockTypes::CATEGORIES => ['categories' => $this->topCategories((int) $block->get('limit', 6), withCovers: true)],
            BlockTypes::PRODUCTS => $this->products($block),
            default => [],
        };
    }

    /** @return array{products: Collection<int, Product>, total: int, categories: Collection<int, Category>} */
    private function products(Block $block): array
    {
        $query = StorefrontProducts::query();
        $category = null;

        if ($block->get('source') === 'category' && $block->get('category_id')) {
            $category = Category::query()->where('is_visible', true)->find($block->get('category_id'));
            $query = $category ? StorefrontProducts::inCategory($category) : $query;
        }

        if ($block->get('source') === 'sale') {
            $query->whereHas('variants', fn ($q) => $q->whereColumn('compare_at_price_cents', '>', 'price_cents'));
        }

        $total = (clone $query)->count();

        return [
            'products' => StorefrontProducts::sort($query, 'novedades')->limit(max(1, min(48, (int) $block->get('limit', 12))))->get(),
            'total' => $total,
            'categories' => $block->get('show_filters', true) ? $this->topCategories(4) : new Collection,
            'category' => $category,
        ];
    }

    /** @return Collection<int, Category> */
    private function topCategories(int $limit, bool $withCovers = false): Collection
    {
        $categories = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_visible', true)->orderBy('position')])
            ->orderBy('position')
            ->limit(max(1, min(12, $limit)))
            ->get();

        if ($withCovers) {
            // Categories have no image: use the photo of the newest product in the department.
            foreach ($categories as $category) {
                $category->setAttribute('cover_url', StorefrontProducts::sort(StorefrontProducts::inCategory($category), 'novedades')
                    ->whereHas('media')
                    ->first()
                    ?->imageUrl('medium'));
            }
        }

        return $categories;
    }
}
