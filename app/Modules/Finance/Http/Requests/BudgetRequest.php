<?php

namespace App\Modules\Finance\Http\Requests;

use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Enums\BudgetPeriod;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $budget = $this->route('budget');

        return $budget instanceof Budget
            ? $this->user()?->can('update', $budget) === true
            : $this->user()?->can('create', Budget::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->where('type', CategoryType::Expense->value)
                    ->whereNull('deleted_at')),
            ],
            'limit_amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'period' => ['required', Rule::enum(BudgetPeriod::class)],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['limit_amount']);
    }
}
