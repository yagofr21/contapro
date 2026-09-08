<?php

namespace App\Modules\Finance\Http\Requests;

use App\Enums\Currency;
use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Models\FinancialGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialGoalRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $goal = $this->route('goal');

        return $goal instanceof FinancialGoal
            ? $this->user()?->can('update', $goal) === true
            : $this->user()?->can('create', FinancialGoal::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')),
            ],
            'target_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['target_amount']);
    }
}
