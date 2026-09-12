<?php

namespace App\Modules\Finance\Queries;

use App\Modules\Finance\Models\Bank;
use Illuminate\Support\Collection;

class BankOptionsQuery
{
    /**
     * @return Collection<int, array{value: string, label: string, color: string, initials: string}>
     */
    public function active(): Collection
    {
        return Bank::query()
            ->where('is_active', true)
            ->orderBy('label')
            ->get()
            ->map(fn (Bank $bank): array => [
                'value' => $bank->code,
                'label' => $bank->label,
                'color' => $bank->color,
                'initials' => $bank->initials,
            ]);
    }
}
