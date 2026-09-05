<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Category;

class UpdateCategory
{
    /** @param array<string, mixed> $data */
    public function handle(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }
}
