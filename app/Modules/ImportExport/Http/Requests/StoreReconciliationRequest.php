<?php

namespace App\Modules\ImportExport\Http\Requests;

use App\Modules\ImportExport\Models\Reconciliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Reconciliation::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at'),
            ],
            'period_start' => ['required', 'date'],
            'statement_date' => ['required', 'date', 'after_or_equal:period_start'],
            'declared_balance' => ['required', 'decimal:0,4'],
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,txt'],
        ];
    }
}
