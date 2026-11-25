<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\Models\Category;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** /api/v1/categories — the department tree (parent_id = parent's id, null for top level). */
class CategoryController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Category::query()->with('parent')->orderBy('parent_id')->orderBy('position')->orderBy('id');
        $query->when($request->query('parent'), fn ($q, $ulid) => $q->whereHas('parent', fn ($p) => $p->where('ulid', $ulid)));
        $query->when($request->has('visible'), fn ($q) => $q->where('is_visible', $request->boolean('visible')));
        $this->filterUpdatedSince($query, $request);

        return CategoryResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->load('parent'));
    }

    public function store(Request $request): JsonResponse
    {
        $category = Category::query()->create($this->attributes($request->validate($this->rules(creating: true))));

        return (new CategoryResource($category->load('parent')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        $data = $request->validate($this->rules(creating: false));

        if (isset($data['parent_id']) && $data['parent_id'] === $category->ulid) {
            throw new DomainRuleException('Una categoría no puede ser su propia categoría madre.');
        }

        $category->update($this->attributes($data));

        return new CategoryResource($category->fresh('parent'));
    }

    public function destroy(Category $category): Response
    {
        if ($category->children()->exists()) {
            throw new DomainRuleException('Mueve o borra antes sus subcategorías.');
        }

        $category->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function rules(bool $creating): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'parent_id' => ['nullable', 'string', Rule::exists('categories', 'ulid')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'is_visible' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    private function attributes(array $data): array
    {
        if (array_key_exists('parent_id', $data)) {
            $data['parent_id'] = $data['parent_id'] ? Category::query()->where('ulid', $data['parent_id'])->value('id') : null;
        }

        return array_intersect_key($data, array_flip(['name', 'parent_id', 'description', 'position', 'is_visible', 'seo_title', 'seo_description']));
    }
}
