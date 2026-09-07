<?php

namespace App\Modules\Finance\Http\Requests;

use App\Http\Requests\NormalizesDecimalInput;
use App\Modules\Finance\Models\Installment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallmentRequest extends FormRequest
{
    use NormalizesDecimalInput;

    public function authorize(): bool
    {
        $installment = $this->route('installment');

        return $installment instanceof Installment
            ? $this->user()?->can('delete', $installment) === true
            : $this->user()?->can('create', Installment::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $accountRule = Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query
            ->where('user_id', $this->user()?->id)
            ->where('is_archived', false)
            ->whereNull('deleted_at'));

        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'account_id' => ['required', 'integer', $accountRule],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()?->id)
                    ->whereNull('deleted_at')),
            ],
            'amount' => ['required', 'decimal:0,4', 'gt:0', 'max:999999999999999.9999'],
            'total_count' => ['required', 'integer', 'min:2', 'max:120'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeDecimalInput(['amount']);
    }
}
