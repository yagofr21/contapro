<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Actions\CreateCategory;
use App\Modules\Finance\Actions\UpdateCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Http\Requests\CategoryRequest;
use App\Modules\Finance\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Category::class);

        return Inertia::render('Categories/Index', [
            'categories' => $request->user()->categories()
                ->with('parent:id,name')
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => $this->serialize($category)),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Category::class);

        return Inertia::render('Categories/Create', $this->formOptions($request));
    }

    public function store(CategoryRequest $request, CreateCategory $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('categories.index')->with('success', 'Categoria criada com sucesso.');
    }

    public function edit(Request $request, Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('Categories/Edit', [
            ...$this->formOptions($request, $category),
            'category' => $this->serialize($category),
        ]);
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $action->handle($category, $request->validated());

        return to_route('categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);
        $category->delete();

        return to_route('categories.index')->with('success', 'Categoria removida com sucesso.');
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request, ?Category $current = null): array
    {
        return [
            'types' => [
                ['value' => CategoryType::Expense->value, 'label' => 'Despesa'],
                ['value' => CategoryType::Income->value, 'label' => 'Receita'],
            ],
            'parentOptions' => $request->user()->categories()
                ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                ]),
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type->value,
            'color' => $category->color,
            'parent_id' => $category->parent_id,
            'parent_name' => $category->parent?->name,
        ];
    }
}
