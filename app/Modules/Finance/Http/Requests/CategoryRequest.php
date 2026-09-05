<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            ? $this->user()?->can('update', $category) === true
            : $this->user()?->can('create', Category::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')),
            ],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $parentId = $this->integer('parent_id');
            $category = $this->route('category');

            if ($category instanceof Category && $parentId === $category->id) {
                $validator->errors()->add('parent_id', 'Uma categoria nao pode ser pai dela mesma.');

                return;
            }

            if ($parentId === 0) {
                return;
            }

            $parent = Category::query()
                ->whereBelongsTo($this->user())
                ->find($parentId);

            if ($parent !== null && $parent->type->value !== $this->string('type')->toString()) {
                $validator->errors()->add('parent_id', 'A categoria pai deve ter o mesmo tipo.');
            }
        }];
    }
}
