<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Models\Portfolio;

class UpdatePortfolio
{
    /** @param array<string, mixed> $data */
    public function handle(Portfolio $portfolio, array $data): Portfolio
    {
        $portfolio->update($data);

        return $portfolio->refresh();
    }
}
