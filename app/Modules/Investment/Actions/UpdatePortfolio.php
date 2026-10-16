<?php

namespace App\Modules\Investment\Actions;

use App\Modules\Investment\Models\Portfolio;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePortfolio
{
    /** @param array<string, mixed> $data */
    public function handle(Portfolio $portfolio, array $data): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $data): Portfolio {
            $portfolio = Portfolio::query()->whereKey($portfolio->id)->lockForUpdate()->firstOrFail();

            if (isset($data['currency']) && $data['currency'] !== $portfolio->currency->value
                && $portfolio->transactions()->exists()) {
                throw ValidationException::withMessages([
                    'currency' => 'A moeda não pode ser alterada após registrar operações na carteira.',
                ]);
            }

            $portfolio->update($data);

            return $portfolio->refresh();
        });
    }
}
